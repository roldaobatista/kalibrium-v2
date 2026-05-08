import React, { useCallback, useEffect, useState } from 'react';
import { useHistory, useParams } from 'react-router-dom';
import { IonContent, IonPage } from '@ionic/react';
import { IonIcon } from '@ionic/react';
import { arrowBackOutline, clipboardOutline, timeOutline } from 'ionicons/icons';
import {
    syncEngine,
    type ServiceOrderEventRow,
    type ServiceOrderRow,
    type ServiceOrderStatus,
} from '../services/syncEngine';
import { secureStorage } from '../services/secureStorage';
import './ServiceOrderDetail.css';

interface UserData {
    id: number;
    name: string;
}

const STATUS_LABEL: Record<string, string> = {
    received: 'Recebido',
    assigned: 'Atribuído',
    in_progress: 'Em andamento',
    paused: 'Pausado',
    in_calibration: 'Em calibração',
    awaiting_approval: 'Aguardando aprovação',
    completed: 'Concluído',
    cancelled: 'Cancelado',
    dispatch_started: 'Despachado',
    arrived_client: 'No cliente',
    left_client: 'Saindo do cliente',
};

const STATUS_CLASS: Record<string, string> = {
    received: 'kb-detail-badge--recebido',
    assigned: 'kb-detail-badge--recebido',
    in_progress: 'kb-detail-badge--andamento',
    paused: 'kb-detail-badge--pausado',
    in_calibration: 'kb-detail-badge--calibrando',
    awaiting_approval: 'kb-detail-badge--aguardando',
    completed: 'kb-detail-badge--concluido',
    cancelled: 'kb-detail-badge--cancelado',
    dispatch_started: 'kb-detail-badge--despachado',
    arrived_client: 'kb-detail-badge--andamento',
    left_client: 'kb-detail-badge--andamento',
};

const MODE_LABEL: Record<string, string> = {
    bench: 'Bancada',
    field_vehicle: 'Campo — veículo',
    field_umc: 'Campo — UMC',
};

const EVENT_TYPE_LABEL: Record<string, string> = {
    status_change: 'Mudança de status',
    note_added: 'Anotação adicionada',
    photo_added: 'Foto adicionada',
    team_changed: 'Equipe alterada',
};

const ServiceOrderDetail: React.FC = () => {
    const history = useHistory();
    const { id } = useParams<{ id: string }>();
    const [order, setOrder] = useState<ServiceOrderRow | null>(null);
    const [events, setEvents] = useState<ServiceOrderEventRow[]>([]);
    const [user, setUser] = useState<UserData | null>(null);
    const [loading, setLoading] = useState(false);

    const carregar = useCallback(async () => {
        const raw = await secureStorage.get('user');
        if (raw) {
            setUser(JSON.parse(raw) as UserData);
        }
        const o = await syncEngine.getServiceOrderById(id);
        if (!o) {
            // Tenta encontrar por server_id se o id é local
            const all = await syncEngine.getServiceOrders();
            const found = all.find((x) => x.server_id === id || x.id === id);
            setOrder(found ?? null);
            if (found) {
                setEvents(await syncEngine.getServiceOrderEvents(found.server_id ?? found.id));
            }
        } else {
            setOrder(o);
            setEvents(await syncEngine.getServiceOrderEvents(o.server_id ?? o.id));
        }
    }, [id]);

    useEffect(() => {
        void carregar();
    }, [carregar]);

    const isFieldMode = (mode: string | undefined): boolean =>
        mode === 'field_vehicle' || mode === 'field_umc';

    const handleStatusChange = async (newStatus: ServiceOrderStatus) => {
        if (!order) return;
        setLoading(true);
        try {
            const agora = new Date().toISOString();
            const entityId = order.server_id ?? order.id;

            await syncEngine.recordChange('service_order', 'update', {
                id: entityId,
                status: newStatus,
                client_name: order.client_name,
                instrument_description: order.instrument_description,
                mode: order.mode,
                notes: order.notes,
                updated_at: agora,
            });

            await syncEngine.recordServiceOrderEvent({
                service_order_id: entityId,
                user_id: user ? String(user.id) : null,
                event_type: 'status_change',
                old_value: order.status,
                new_value: newStatus,
                metadata: null,
                created_at: agora,
            });

            await carregar();
        } finally {
            setLoading(false);
        }
    };

    const getActions = (): { label: string; newStatus: ServiceOrderStatus; variant: 'primary' | 'secondary' }[] => {
        if (!order) return [];
        const status = order.status;
        const field = isFieldMode(order.mode);

        if (status === 'received') {
            return [
                {
                    label: 'Iniciar',
                    newStatus: field ? 'dispatch_started' : 'in_progress',
                    variant: 'primary',
                },
            ];
        }
        if (status === 'dispatch_started') {
            return [{ label: 'Cheguei no cliente', newStatus: 'arrived_client', variant: 'primary' }];
        }
        if (status === 'arrived_client') {
            return [{ label: 'Sair do cliente', newStatus: 'left_client', variant: 'primary' }];
        }
        if (status === 'left_client' || status === 'in_progress' || status === 'in_calibration') {
            return [
                { label: 'Concluir', newStatus: 'completed', variant: 'primary' },
                { label: 'Pausar', newStatus: 'paused', variant: 'secondary' },
            ];
        }
        if (status === 'paused') {
            return [{ label: 'Retomar', newStatus: 'in_progress', variant: 'primary' }];
        }
        return [];
    };

    const actions = getActions();

    return (
        <IonPage className="kb-detail-page">
            <header className="kb-detail-header">
                <button
                    type="button"
                    className="kb-detail-voltar"
                    onClick={() => history.push('/queue')}
                    aria-label="Voltar para fila"
                >
                    <IonIcon icon={arrowBackOutline} style={{ fontSize: '22px' }} />
                </button>
                <span className="kb-detail-titulo">Detalhes da OS</span>
            </header>

            <IonContent className="kb-detail-content-wrapper">
                <div className="kb-detail-content">
                    {!order ? (
                        <div className="kb-lista-vazia">
                            <IonIcon icon={clipboardOutline} className="kb-lista-vazia-icone" aria-hidden="true" />
                            <p className="kb-lista-vazia-titulo">Ordem não encontrada</p>
                        </div>
                    ) : (
                        <>
                            {/* Card principal */}
                            <div className="kb-detail-card">
                                <div className="kb-detail-card-topo">
                                    <span className="kb-detail-card-cliente">{order.client_name}</span>
                                    <span className={`kb-detail-badge ${STATUS_CLASS[order.status] ?? ''}`}>
                                        {STATUS_LABEL[order.status] ?? order.status}
                                    </span>
                                </div>
                                <span className="kb-detail-card-modo">
                                    {MODE_LABEL[order.mode ?? 'bench'] ?? order.mode}
                                </span>
                                <span className="kb-detail-card-instrumento">{order.instrument_description}</span>
                                {order.notes && (
                                    <p className="kb-detail-card-notas">{order.notes}</p>
                                )}
                                {order.team_members && order.team_members.length > 0 && (
                                    <div className="kb-detail-equipe">
                                        <span className="kb-detail-equipe-titulo">Equipe</span>
                                        <div className="kb-detail-equipe-lista">
                                            {order.team_members.map((m) => (
                                                <span key={m.user_id} className="kb-detail-equipe-item">
                                                    {m.name} ({m.role})
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                {order.pending_sync === 1 && (
                                    <span className="kb-detail-card-pendente">⏳ Aguardando sincronizar</span>
                                )}
                            </div>

                            {/* Ações */}
                            {actions.length > 0 && (
                                <div className="kb-detail-acoes">
                                    {actions.map((a) => (
                                        <button
                                            key={a.newStatus}
                                            type="button"
                                            className={`kb-detail-acao ${a.variant === 'primary' ? 'kb-detail-acao--primaria' : 'kb-detail-acao--secundaria'}`}
                                            onClick={() => void handleStatusChange(a.newStatus)}
                                            disabled={loading}
                                        >
                                            {loading ? 'Processando…' : a.label}
                                        </button>
                                    ))}
                                </div>
                            )}

                            {/* Timeline */}
                            <div className="kb-detail-timeline">
                                <h3 className="kb-detail-timeline-titulo">
                                    <IonIcon icon={timeOutline} style={{ fontSize: '16px' }} />
                                    Histórico
                                </h3>
                                {events.length === 0 ? (
                                    <p className="kb-detail-timeline-vazio">Nenhum evento registrado ainda.</p>
                                ) : (
                                    <ul className="kb-detail-timeline-lista">
                                        {events.map((e) => (
                                            <li key={e.id} className="kb-detail-timeline-item">
                                                <div className="kb-detail-timeline-ponto" />
                                                <div className="kb-detail-timeline-conteudo">
                                                    <span className="kb-detail-timeline-tipo">
                                                        {EVENT_TYPE_LABEL[e.event_type] ?? e.event_type}
                                                    </span>
                                                    {e.old_value !== null && e.new_value !== null && (
                                                        <span className="kb-detail-timeline-valores">
                                                            {STATUS_LABEL[e.old_value] ?? e.old_value}
                                                            {' → '}
                                                            {STATUS_LABEL[e.new_value] ?? e.new_value}
                                                        </span>
                                                    )}
                                                    <span className="kb-detail-timeline-data">
                                                        {new Date(e.created_at).toLocaleString('pt-BR')}
                                                    </span>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </>
                    )}
                </div>
            </IonContent>
        </IonPage>
    );
};

export default ServiceOrderDetail;

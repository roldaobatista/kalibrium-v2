import React, { useCallback, useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import { IonContent, IonPage } from '@ionic/react';
import { IonIcon } from '@ionic/react';
import { arrowBackOutline, clipboardOutline, funnelOutline } from 'ionicons/icons';
import { syncEngine, type ServiceOrderRow } from '../services/syncEngine';
import { secureStorage } from '../services/secureStorage';
import './Queue.css';

type FilterType = 'today' | 'all' | 'completed';

interface UserData {
    id: number;
    name: string;
}

function isToday(isoDate: string): boolean {
    const d = new Date(isoDate);
    const now = new Date();
    return (
        d.getDate() === now.getDate() &&
        d.getMonth() === now.getMonth() &&
        d.getFullYear() === now.getFullYear()
    );
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
    received: 'kb-queue-badge--recebido',
    assigned: 'kb-queue-badge--recebido',
    in_progress: 'kb-queue-badge--andamento',
    paused: 'kb-queue-badge--pausado',
    in_calibration: 'kb-queue-badge--calibrando',
    awaiting_approval: 'kb-queue-badge--aguardando',
    completed: 'kb-queue-badge--concluido',
    cancelled: 'kb-queue-badge--cancelado',
    dispatch_started: 'kb-queue-badge--despachado',
    arrived_client: 'kb-queue-badge--andamento',
    left_client: 'kb-queue-badge--andamento',
};

const MODE_LABEL: Record<string, string> = {
    bench: 'Bancada',
    field_vehicle: 'Campo — veículo',
    field_umc: 'Campo — UMC',
};

const Queue: React.FC = () => {
    const history = useHistory();
    const [orders, setOrders] = useState<ServiceOrderRow[]>([]);
    const [filter, setFilter] = useState<FilterType>('all');
    const [user, setUser] = useState<UserData | null>(null);

    const carregar = useCallback(async () => {
        const raw = await secureStorage.get('user');
        if (!raw) {
            history.replace('/login');
            return;
        }
        const me = JSON.parse(raw) as UserData;
        setUser(me);

        const all = await syncEngine.getServiceOrders();
        const mine = all.filter((o) => {
            if (o.deleted) return false;
            const isOwner = o.user_id === me.id;
            const members = o.team_members ?? [];
            const isMember = members.some((m) => m.user_id === me.id);
            return isOwner || isMember;
        });
        setOrders(mine);
    }, [history]);

    useEffect(() => {
        void carregar();
    }, [carregar]);

    const filtered = orders.filter((o) => {
        if (filter === 'today') return isToday(o.updated_at);
        if (filter === 'completed') return o.status === 'completed' || o.status === 'cancelled';
        return true;
    });

    const sortOrders = (list: ServiceOrderRow[]): ServiceOrderRow[] => {
        const orderMap: Record<string, number> = {
            received: 1,
            assigned: 2,
            in_progress: 3,
            paused: 4,
            dispatch_started: 5,
            arrived_client: 6,
            left_client: 7,
            in_calibration: 8,
            awaiting_approval: 9,
            completed: 10,
            cancelled: 11,
        };
        return [...list].sort((a, b) => {
            const ra = orderMap[a.status] ?? 99;
            const rb = orderMap[b.status] ?? 99;
            if (ra !== rb) return ra - rb;
            return b.updated_at.localeCompare(a.updated_at);
        });
    };

    const sorted = sortOrders(filtered);

    return (
        <IonPage className="kb-queue-page">
            <header className="kb-queue-header">
                <button
                    type="button"
                    className="kb-queue-voltar"
                    onClick={() => history.push('/home')}
                    aria-label="Voltar para início"
                >
                    <IonIcon icon={arrowBackOutline} style={{ fontSize: '22px' }} />
                </button>
                <span className="kb-queue-titulo">Minha fila</span>
                <span className="kb-queue-contagem">{sorted.length}</span>
            </header>

            <div className="kb-queue-filtros">
                <button
                    type="button"
                    className={`kb-queue-filtro ${filter === 'today' ? 'kb-queue-filtro--ativo' : ''}`}
                    onClick={() => setFilter(filter === 'today' ? 'all' : 'today')}
                >
                    <IonIcon icon={funnelOutline} style={{ fontSize: '12px' }} />
                    Hoje
                </button>
                <button
                    type="button"
                    className={`kb-queue-filtro ${filter === 'all' ? 'kb-queue-filtro--ativo' : ''}`}
                    onClick={() => setFilter('all')}
                >
                    Todas
                </button>
                <button
                    type="button"
                    className={`kb-queue-filtro ${filter === 'completed' ? 'kb-queue-filtro--ativo' : ''}`}
                    onClick={() => setFilter(filter === 'completed' ? 'all' : 'completed')}
                >
                    Concluídas
                </button>
            </div>

            <IonContent className="kb-queue-content-wrapper">
                <div className="kb-queue-content">
                    {sorted.length === 0 ? (
                        <div className="kb-lista-vazia">
                            <IonIcon icon={clipboardOutline} className="kb-lista-vazia-icone" aria-hidden="true" />
                            <p className="kb-lista-vazia-titulo">Nenhuma ordem na fila</p>
                            <p className="kb-lista-vazia-desc">
                                {filter === 'today'
                                    ? 'Você não tem ordens para hoje.'
                                    : 'Suas ordens aparecem aqui quando forem atribuídas.'}
                            </p>
                        </div>
                    ) : (
                        <ul className="kb-queue-lista" aria-label="Lista de ordens na fila">
                            {sorted.map((ordem) => (
                                <li key={ordem.id}>
                                    <button
                                        type="button"
                                        className="kb-queue-card"
                                        onClick={() => history.push(`/service-order/${ordem.server_id ?? ordem.id}`)}
                                        aria-label={`Ver detalhes de ${ordem.client_name}`}
                                    >
                                        <div className="kb-queue-card-topo">
                                            <span className="kb-queue-card-cliente">{ordem.client_name}</span>
                                            <span className={`kb-queue-badge ${STATUS_CLASS[ordem.status] ?? ''}`}>
                                                {STATUS_LABEL[ordem.status] ?? ordem.status}
                                            </span>
                                        </div>
                                        <span className="kb-queue-card-modo">{MODE_LABEL[ordem.mode ?? 'bench'] ?? ordem.mode}</span>
                                        <span className="kb-queue-card-instrumento">{ordem.instrument_description}</span>
                                        <span className="kb-queue-card-data">
                                            {new Date(ordem.updated_at).toLocaleDateString('pt-BR')}
                                        </span>
                                        {ordem.pending_sync === 1 && (
                                            <span className="kb-queue-card-pendente">⏳ Aguardando sincronizar</span>
                                        )}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </IonContent>
        </IonPage>
    );
};

export default Queue;

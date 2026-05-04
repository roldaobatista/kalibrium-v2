import React, { useCallback, useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import { IonContent, IonPage } from '@ionic/react';
import { IonIcon } from '@ionic/react';
import { addOutline, arrowBackOutline, createOutline, clipboardOutline } from 'ionicons/icons';
import { syncEngine, type ServiceOrderRow, type ServiceOrderStatus } from '../services/syncEngine';
import { secureStorage } from '../services/secureStorage';
import './ServiceOrders.css';

const STATUS_OPTIONS: { value: ServiceOrderStatus; label: string }[] = [
    { value: 'received', label: 'Recebido' },
    { value: 'in_calibration', label: 'Em calibração' },
    { value: 'awaiting_approval', label: 'Aguardando aprovação' },
    { value: 'completed', label: 'Concluído' },
    { value: 'cancelled', label: 'Cancelado' },
];

const STATUS_CLASS: Record<ServiceOrderStatus, string> = {
    received: 'kb-os-badge--recebido',
    in_calibration: 'kb-os-badge--calibrando',
    awaiting_approval: 'kb-os-badge--aguardando',
    completed: 'kb-os-badge--concluido',
    cancelled: 'kb-os-badge--cancelado',
};

const STATUS_LABEL: Record<ServiceOrderStatus, string> = {
    received: 'Recebido',
    in_calibration: 'Em calibração',
    awaiting_approval: 'Aguardando aprovação',
    completed: 'Concluído',
    cancelled: 'Cancelado',
};

const ServiceOrders: React.FC = () => {
    const history = useHistory();
    const [orders, setOrders] = useState<ServiceOrderRow[]>([]);
    const [modalAberto, setModalAberto] = useState(false);
    const [editando, setEditando] = useState<ServiceOrderRow | null>(null);
    const [clientName, setClientName] = useState('');
    const [instrumentDescription, setInstrumentDescription] = useState('');
    const [status, setStatus] = useState<ServiceOrderStatus>('received');
    const [notes, setNotes] = useState('');
    const [salvando, setSalvando] = useState(false);
    const [erro, setErro] = useState('');

    const carregarOS = useCallback(async () => {
        const token = await secureStorage.get('token');
        if (!token) {
            history.replace('/login');
            return;
        }
        const lista = await syncEngine.getServiceOrders();
        setOrders(lista);
    }, [history]);

    useEffect(() => {
        void carregarOS();
    }, [carregarOS]);

    const abrirModalNova = () => {
        setEditando(null);
        setClientName('');
        setInstrumentDescription('');
        setStatus('received');
        setNotes('');
        setErro('');
        setModalAberto(true);
    };

    const abrirModalEditar = (ordem: ServiceOrderRow) => {
        setEditando(ordem);
        setClientName(ordem.client_name);
        setInstrumentDescription(ordem.instrument_description);
        setStatus(ordem.status);
        setNotes(ordem.notes ?? '');
        setErro('');
        setModalAberto(true);
    };

    const fecharModal = () => {
        setModalAberto(false);
        setEditando(null);
        setErro('');
    };

    const salvar = async () => {
        if (!clientName.trim()) {
            setErro('O nome do cliente é obrigatório.');
            return;
        }
        if (!instrumentDescription.trim()) {
            setErro('A descrição do instrumento é obrigatória.');
            return;
        }
        setSalvando(true);
        setErro('');
        try {
            const agora = new Date().toISOString();
            if (editando) {
                await syncEngine.recordChange('service_order', 'update', {
                    id: editando.server_id ?? editando.id,
                    client_name: clientName.trim(),
                    instrument_description: instrumentDescription.trim(),
                    status,
                    notes: notes.trim() || null,
                    updated_at: agora,
                });
            } else {
                await syncEngine.recordChange('service_order', 'create', {
                    client_name: clientName.trim(),
                    instrument_description: instrumentDescription.trim(),
                    status,
                    notes: notes.trim() || null,
                    updated_at: agora,
                });
            }
            fecharModal();
            await carregarOS();
        } finally {
            setSalvando(false);
        }
    };

    const pendentes = orders.filter((o) => o.pending_sync === 1).length;

    return (
        <IonPage className="kb-os-page">
            <header className="kb-os-header">
                <button
                    type="button"
                    className="kb-os-voltar"
                    onClick={() => history.push('/home')}
                    aria-label="Voltar para início"
                >
                    <IonIcon icon={arrowBackOutline} style={{ fontSize: '22px' }} />
                </button>
                <span className="kb-os-titulo">Ordens de Serviço</span>
                {pendentes > 0 && (
                    <span className="kb-os-pendentes-badge" aria-live="polite">
                        {pendentes} pendente{pendentes > 1 ? 's' : ''}
                    </span>
                )}
            </header>

            <IonContent className="kb-os-content-wrapper">
                <div className="kb-os-content">
                    {orders.length === 0 ? (
                        <div className="kb-lista-vazia">
                            <IonIcon
                                icon={clipboardOutline}
                                className="kb-lista-vazia-icone"
                                aria-hidden="true"
                            />
                            <p className="kb-lista-vazia-titulo">Nenhuma ordem de serviço ainda</p>
                            <p className="kb-lista-vazia-desc">
                                Toque no botão + para registrar a primeira.
                            </p>
                        </div>
                    ) : (
                        <ul className="kb-os-lista" aria-label="Lista de ordens de serviço">
                            {orders.map((ordem) => (
                                <li key={ordem.id} className="kb-os-item">
                                    <button
                                        type="button"
                                        className="kb-os-item-btn"
                                        onClick={() => abrirModalEditar(ordem)}
                                        aria-label={`Editar ordem de ${ordem.client_name}`}
                                    >
                                        <div className="kb-os-item-topo">
                                            <span className="kb-os-item-cliente">
                                                {ordem.client_name}
                                            </span>
                                            <span
                                                className={`kb-os-badge ${STATUS_CLASS[ordem.status]}`}
                                            >
                                                {STATUS_LABEL[ordem.status]}
                                            </span>
                                        </div>
                                        <span className="kb-os-item-instrumento">
                                            {ordem.instrument_description}
                                        </span>
                                        {ordem.pending_sync === 1 ? (
                                            <span
                                                className="kb-os-item-pendente"
                                                aria-label="Aguardando sincronizar"
                                            >
                                                ⏳ Aguardando sincronizar
                                            </span>
                                        ) : null}
                                    </button>
                                    <button
                                        type="button"
                                        className="kb-os-btn-editar"
                                        onClick={() => abrirModalEditar(ordem)}
                                        aria-label={`Editar ${ordem.client_name}`}
                                    >
                                        <IonIcon icon={createOutline} />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </IonContent>

            {/* Botão flutuante */}
            <button
                type="button"
                className="kb-os-fab"
                onClick={abrirModalNova}
                aria-label="Nova ordem de serviço"
            >
                <IonIcon icon={addOutline} style={{ fontSize: '28px' }} />
            </button>

            {/* Modal */}
            {modalAberto && (
                <div
                    className="kb-modal-overlay"
                    role="dialog"
                    aria-modal="true"
                    aria-label={editando ? 'Editar ordem de serviço' : 'Nova ordem de serviço'}
                >
                    <div className="kb-modal">
                        <h2 className="kb-modal-titulo">
                            {editando ? 'Editar ordem de serviço' : 'Nova ordem de serviço'}
                        </h2>
                        {erro && <p className="kb-modal-erro">{erro}</p>}
                        <input
                            className="kb-modal-input"
                            type="text"
                            placeholder="Cliente (ex: Acme Indústria Ltda)"
                            value={clientName}
                            onChange={(e) => setClientName(e.target.value)}
                            maxLength={255}
                            autoFocus
                        />
                        <input
                            className="kb-modal-input"
                            type="text"
                            placeholder="Instrumento (ex: Paquímetro Mitutoyo 200mm)"
                            value={instrumentDescription}
                            onChange={(e) => setInstrumentDescription(e.target.value)}
                            maxLength={255}
                        />
                        <select
                            className="kb-modal-select"
                            value={status}
                            onChange={(e) => setStatus(e.target.value as ServiceOrderStatus)}
                            aria-label="Status da ordem de serviço"
                        >
                            {STATUS_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </option>
                            ))}
                        </select>
                        <textarea
                            className="kb-modal-textarea"
                            placeholder="Observações (opcional)"
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            rows={4}
                        />
                        <div className="kb-modal-acoes">
                            <button
                                type="button"
                                className="kb-modal-btn-cancelar"
                                onClick={fecharModal}
                                disabled={salvando}
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                className="kb-modal-btn-salvar"
                                onClick={() => void salvar()}
                                disabled={
                                    salvando || !clientName.trim() || !instrumentDescription.trim()
                                }
                            >
                                {salvando ? 'Salvando…' : 'Salvar'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </IonPage>
    );
};

export default ServiceOrders;

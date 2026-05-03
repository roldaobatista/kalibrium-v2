import React, { useCallback, useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import { IonContent, IonPage } from '@ionic/react';
import { IonIcon } from '@ionic/react';
import { addOutline, arrowBackOutline, trashOutline, createOutline } from 'ionicons/icons';
import { syncEngine, type NoteRow } from '../services/syncEngine';
import { secureStorage } from '../services/secureStorage';
import './Notes.css';

const Notes: React.FC = () => {
    const history = useHistory();
    const [notes, setNotes] = useState<NoteRow[]>([]);
    const [modalAberto, setModalAberto] = useState(false);
    const [editando, setEditando] = useState<NoteRow | null>(null);
    const [titulo, setTitulo] = useState('');
    const [corpo, setCorpo] = useState('');
    const [salvando, setSalvando] = useState(false);

    const carregarNotas = useCallback(async () => {
        const token = await secureStorage.get('token');
        if (!token) {
            history.replace('/login');
            return;
        }
        const lista = await syncEngine.getNotes();
        setNotes(lista);
    }, [history]);

    useEffect(() => {
        void carregarNotas();
    }, [carregarNotas]);

    const abrirModalNova = () => {
        setEditando(null);
        setTitulo('');
        setCorpo('');
        setModalAberto(true);
    };

    const abrirModalEditar = (nota: NoteRow) => {
        setEditando(nota);
        setTitulo(nota.title);
        setCorpo(nota.body);
        setModalAberto(true);
    };

    const fecharModal = () => {
        setModalAberto(false);
        setEditando(null);
        setTitulo('');
        setCorpo('');
    };

    const salvar = async () => {
        if (!titulo.trim()) return;
        setSalvando(true);
        try {
            const agora = new Date().toISOString();
            if (editando) {
                await syncEngine.recordChange('note', 'update', {
                    id: editando.server_id ?? editando.id,
                    title: titulo.trim(),
                    body: corpo.trim(),
                    updated_at: agora,
                });
            } else {
                await syncEngine.recordChange('note', 'create', {
                    title: titulo.trim(),
                    body: corpo.trim(),
                    updated_at: agora,
                });
            }
            fecharModal();
            await carregarNotas();
        } finally {
            setSalvando(false);
        }
    };

    const apagar = async (nota: NoteRow) => {
        await syncEngine.recordChange('note', 'delete', {
            id: nota.server_id ?? nota.id,
            updated_at: new Date().toISOString(),
        });
        await carregarNotas();
    };

    const pendentes = notes.filter((n) => n.pending_sync === 1).length;

    return (
        <IonPage className="kb-notes-page">
            <header className="kb-notes-header">
                <button
                    type="button"
                    className="kb-notes-voltar"
                    onClick={() => history.push('/home')}
                    aria-label="Voltar para início"
                >
                    <IonIcon icon={arrowBackOutline} style={{ fontSize: '22px' }} />
                </button>
                <span className="kb-notes-titulo">Anotações</span>
                {pendentes > 0 && (
                    <span className="kb-notes-pendentes-badge" aria-live="polite">
                        {pendentes} pendente{pendentes > 1 ? 's' : ''}
                    </span>
                )}
            </header>

            <IonContent className="kb-notes-content-wrapper">
                <div className="kb-notes-content">
                    {notes.length === 0 ? (
                        <div className="kb-lista-vazia">
                            <IonIcon
                                icon={createOutline}
                                className="kb-lista-vazia-icone"
                                aria-hidden="true"
                            />
                            <p className="kb-lista-vazia-titulo">Nenhuma anotação ainda</p>
                            <p className="kb-lista-vazia-desc">
                                Toque no botão + para criar a primeira.
                            </p>
                        </div>
                    ) : (
                        <ul className="kb-notes-lista" aria-label="Lista de anotações">
                            {notes.map((nota) => (
                                <li key={nota.id} className="kb-note-item">
                                    <div className="kb-note-item-conteudo">
                                        <span className="kb-note-item-titulo">{nota.title}</span>
                                        {nota.body ? (
                                            <span className="kb-note-item-corpo">{nota.body}</span>
                                        ) : null}
                                        {nota.pending_sync === 1 ? (
                                            <span
                                                className="kb-note-item-pendente"
                                                aria-label="Aguardando sincronizar"
                                            >
                                                ⏳ Aguardando sincronizar
                                            </span>
                                        ) : null}
                                    </div>
                                    <div className="kb-note-item-acoes">
                                        <button
                                            type="button"
                                            className="kb-note-btn-editar"
                                            onClick={() => abrirModalEditar(nota)}
                                            aria-label={`Editar ${nota.title}`}
                                        >
                                            <IonIcon icon={createOutline} />
                                        </button>
                                        <button
                                            type="button"
                                            className="kb-note-btn-apagar"
                                            onClick={() => void apagar(nota)}
                                            aria-label={`Apagar ${nota.title}`}
                                        >
                                            <IonIcon icon={trashOutline} />
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </IonContent>

            {/* Botão flutuante */}
            <button
                type="button"
                className="kb-notes-fab"
                onClick={abrirModalNova}
                aria-label="Nova anotação"
            >
                <IonIcon icon={addOutline} style={{ fontSize: '28px' }} />
            </button>

            {/* Modal */}
            {modalAberto && (
                <div
                    className="kb-modal-overlay"
                    role="dialog"
                    aria-modal="true"
                    aria-label={editando ? 'Editar anotação' : 'Nova anotação'}
                >
                    <div className="kb-modal">
                        <h2 className="kb-modal-titulo">
                            {editando ? 'Editar anotação' : 'Nova anotação'}
                        </h2>
                        <input
                            className="kb-modal-input"
                            type="text"
                            placeholder="Título"
                            value={titulo}
                            onChange={(e) => setTitulo(e.target.value)}
                            maxLength={255}
                            autoFocus
                        />
                        <textarea
                            className="kb-modal-textarea"
                            placeholder="Texto da anotação"
                            value={corpo}
                            onChange={(e) => setCorpo(e.target.value)}
                            rows={5}
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
                                disabled={salvando || !titulo.trim()}
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

export default Notes;

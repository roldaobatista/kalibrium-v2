import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import { IonContent, IonPage } from '@ionic/react';
import { IonIcon } from '@ionic/react';
import {
    menuOutline,
    closeOutline,
    clipboardOutline,
    homeOutline,
    logOutOutline,
} from 'ionicons/icons';
import { apiFetch } from '../services/api';
import * as biometric from '../services/biometric';
import { secureStorage } from '../services/secureStorage';
import Avatar from '../components/Avatar';
import './Home.css';

interface UserData {
    id: number;
    name: string;
    email: string;
}

function primeiroNome(nomeCompleto: string): string {
    return nomeCompleto.trim().split(' ')[0] ?? nomeCompleto;
}

const Home: React.FC = () => {
    const history = useHistory();
    const [user, setUser] = useState<UserData | null>(null);
    const [drawerAberto, setDrawerAberto] = useState(false);
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    // Carrega user do storage e valida sessão em background
    useEffect(() => {
        const init = async () => {
            const token = await secureStorage.get('token');
            if (!token) {
                history.replace('/login');
                return;
            }

            const raw = await secureStorage.get('user');
            if (raw) {
                try {
                    setUser(JSON.parse(raw) as UserData);
                } catch {
                    history.replace('/login');
                    return;
                }
            } else {
                history.replace('/login');
                return;
            }

            // Valida sessão com backend sem bloquear render (falha silenciosa se offline)
            void apiFetch('/api/mobile/me')
                .then((res) => {
                    if (res.status === 200) {
                        void res.json().then((data: UserData) => setUser(data));
                    }
                })
                .catch(() => {
                    // Offline ou erro — mantém dados do storage
                });
        };

        void init();
    }, [history]);

    // Indicador online/offline reativo
    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const handleSair = async () => {
        await biometric.clear();
        await secureStorage.clear();
        localStorage.removeItem('kalibrium.biometric_optout');
        history.replace('/login');
    };

    return (
        <IonPage className="kb-home-page">
            {/* Backdrop do drawer */}
            {drawerAberto && (
                <div
                    className="kb-drawer-backdrop"
                    onClick={() => setDrawerAberto(false)}
                    aria-hidden="true"
                />
            )}

            {/* Drawer lateral */}
            <aside
                className={`kb-drawer${drawerAberto ? ' kb-drawer--aberto' : ''}`}
                aria-label="Menu lateral"
            >
                {/* Cabeçalho do drawer */}
                <div className="kb-drawer-header">
                    {user && (
                        <div className="kb-drawer-perfil">
                            <Avatar name={user.name} size={48} />
                            <div className="kb-drawer-perfil-info">
                                <span className="kb-drawer-nome">{user.name}</span>
                                <span className="kb-drawer-email">{user.email}</span>
                            </div>
                        </div>
                    )}
                    <button
                        type="button"
                        className="kb-drawer-fechar"
                        onClick={() => setDrawerAberto(false)}
                        aria-label="Fechar menu"
                    >
                        <IonIcon icon={closeOutline} style={{ fontSize: '22px' }} />
                    </button>
                </div>

                {/* Navegação */}
                <nav className="kb-drawer-nav">
                    <button
                        type="button"
                        className="kb-drawer-item kb-drawer-item--ativo"
                        aria-current="page"
                    >
                        <IonIcon icon={homeOutline} className="kb-drawer-item-icone" />
                        Início
                    </button>
                </nav>

                {/* Divisor */}
                <div className="kb-drawer-divisor" />

                {/* Rodapé do drawer — Sair */}
                <div className="kb-drawer-rodape">
                    <button
                        type="button"
                        className="kb-drawer-sair"
                        onClick={() => void handleSair()}
                    >
                        <IonIcon icon={logOutOutline} className="kb-drawer-item-icone" />
                        Sair
                    </button>
                </div>
            </aside>

            {/* Cabeçalho fixo */}
            <header className="kb-home-header">
                <span className="kb-home-saudacao">
                    Olá, {user ? primeiroNome(user.name) : '…'}
                </span>
                <button
                    type="button"
                    className="kb-home-menu-btn"
                    onClick={() => setDrawerAberto(true)}
                    aria-label="Abrir menu"
                >
                    <IonIcon icon={menuOutline} style={{ fontSize: '24px' }} />
                </button>
            </header>

            <IonContent className="kb-home-content-wrapper">
                <div className="kb-home-content">
                    {/* Cards de resumo */}
                    <div className="kb-home-cards">
                        {/* Card: Ordens de hoje */}
                        <div className="kb-card">
                            <div className="kb-card-titulo">
                                <IonIcon icon={clipboardOutline} className="kb-card-icone" />
                                Ordens de hoje
                            </div>
                            <p className="kb-card-valor">0 ordens atribuídas pra hoje</p>
                            <p className="kb-card-desc">Em breve, suas tarefas aparecem aqui.</p>
                        </div>

                        {/* Card: Status de conexão */}
                        <div className="kb-card">
                            <div className="kb-card-titulo">
                                <span
                                    className={`kb-status-dot${isOnline ? ' kb-status-dot--online' : ' kb-status-dot--offline'}`}
                                    aria-hidden="true"
                                />
                                {isOnline ? 'Online' : 'Sem sinal'}
                            </div>
                            <p className="kb-card-desc">
                                {isOnline
                                    ? 'Você está conectado.'
                                    : 'Trabalhando offline. As mudanças vão sincronizar quando voltar a conexão.'}
                            </p>
                        </div>
                    </div>

                    {/* Lista vazia */}
                    <div className="kb-lista-vazia">
                        <IonIcon
                            icon={clipboardOutline}
                            className="kb-lista-vazia-icone"
                            aria-hidden="true"
                        />
                        <p className="kb-lista-vazia-titulo">Sem ordens de serviço por enquanto</p>
                        <p className="kb-lista-vazia-desc">
                            Quando o gerente atribuir, aparecem aqui.
                        </p>
                    </div>
                </div>
            </IonContent>
        </IonPage>
    );
};

export default Home;

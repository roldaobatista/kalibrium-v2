import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import { IonContent, IonHeader, IonPage, IonTitle, IonToolbar } from '@ionic/react';
import { apiFetch } from '../services/api';
import * as biometric from '../services/biometric';
import './Home.css';

interface UserData {
    id: number;
    name: string;
    email: string;
}

const Home: React.FC = () => {
    const history = useHistory();
    const [user, setUser] = useState<UserData | null>(null);

    useEffect(() => {
        const token = localStorage.getItem('kalibrium.token');
        if (!token) {
            history.replace('/login');
            return;
        }

        // Carrega dados do usuário do localStorage enquanto valida sessão com o servidor.
        const raw = localStorage.getItem('kalibrium.user');
        if (raw) {
            try {
                setUser(JSON.parse(raw) as UserData);
            } catch {
                history.replace('/login');
                return;
            }
        }

        // Valida sessão com o backend. Se receber wipe, apiFetch cuida do redirect.
        void apiFetch('/api/mobile/me').then((res) => {
            if (res.status === 200) {
                void res.json().then((data: UserData) => setUser(data));
            }
        });
    }, [history]);

    const handleSair = async () => {
        await biometric.clear();
        localStorage.removeItem('kalibrium.token');
        localStorage.removeItem('kalibrium.user');
        history.replace('/login');
    };

    return (
        <IonPage className="kb-home-page">
            <IonHeader>
                <IonToolbar className="kb-home-toolbar">
                    <IonTitle className="kb-home-title">Início</IonTitle>
                </IonToolbar>
            </IonHeader>

            <IonContent>
                <div className="kb-home-content">
                    <p className="kb-home-boas-vindas">Bem-vindo{user ? `, ${user.name}` : ''}!</p>

                    <div className="kb-home-rodape">
                        <button
                            type="button"
                            className="kb-btn-sair"
                            onClick={() => void handleSair()}
                        >
                            Sair
                        </button>
                    </div>
                </div>
            </IonContent>
        </IonPage>
    );
};

export default Home;

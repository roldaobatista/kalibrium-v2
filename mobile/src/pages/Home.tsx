import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import { IonButton, IonContent, IonHeader, IonPage, IonTitle, IonToolbar } from '@ionic/react';
import * as biometric from '../services/biometric';

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
        const raw = localStorage.getItem('kalibrium.user');
        if (raw) {
            try {
                setUser(JSON.parse(raw) as UserData);
            } catch {
                // dado corrompido — volta pro login
                history.replace('/login');
            }
        }
    }, [history]);

    const handleSair = async () => {
        // Apaga credenciais biométricas antes de limpar o localStorage
        // para que o próximo login exija senha novamente
        await biometric.clear();

        localStorage.removeItem('kalibrium.token');
        localStorage.removeItem('kalibrium.user');
        history.replace('/login');
    };

    return (
        <IonPage>
            <IonHeader>
                <IonToolbar>
                    <IonTitle>Início</IonTitle>
                </IonToolbar>
            </IonHeader>

            <IonContent className="ion-padding">
                <p style={{ fontSize: '1.2rem', marginTop: '2rem' }}>
                    Bem-vindo{user ? `, ${user.name}` : ''}!
                </p>

                <IonButton
                    expand="block"
                    color="medium"
                    style={{ marginTop: '2rem' }}
                    onClick={() => void handleSair()}
                >
                    Sair
                </IonButton>
            </IonContent>
        </IonPage>
    );
};

export default Home;

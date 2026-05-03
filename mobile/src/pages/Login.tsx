import React, { useState } from 'react';
import {
    IonButton,
    IonContent,
    IonHeader,
    IonInput,
    IonItem,
    IonLabel,
    IonPage,
    IonTitle,
    IonToast,
    IonToolbar,
} from '@ionic/react';
import './Login.css';

const Login: React.FC = () => {
    const [email, setEmail] = useState('');
    const [senha, setSenha] = useState('');
    const [toastAberto, setToastAberto] = useState(false);
    const [mensagemToast, setMensagemToast] = useState('');

    const handleEntrar = () => {
        if (!email.trim() || !senha.trim()) {
            setMensagemToast('Preencha e-mail e senha pra entrar.');
            setToastAberto(true);
            return;
        }

        setMensagemToast('Login ainda não conectado ao servidor — próxima rodada.');
        setToastAberto(true);
    };

    return (
        <IonPage>
            <IonHeader>
                <IonToolbar>
                    <IonTitle>Kalibrium</IonTitle>
                </IonToolbar>
            </IonHeader>

            <IonContent className="login-content">
                <div className="login-container">
                    <p className="login-subtitulo">Acesso do técnico</p>

                    <IonItem className="login-item">
                        <IonLabel position="floating">E-mail</IonLabel>
                        <IonInput
                            type="email"
                            placeholder="seu@email.com"
                            value={email}
                            onIonInput={(e) => setEmail(e.detail.value ?? '')}
                            autocomplete="email"
                        />
                    </IonItem>

                    <IonItem className="login-item">
                        <IonLabel position="floating">Senha</IonLabel>
                        <IonInput
                            type="password"
                            placeholder="sua senha"
                            value={senha}
                            onIonInput={(e) => setSenha(e.detail.value ?? '')}
                            autocomplete="current-password"
                        />
                    </IonItem>

                    <IonButton
                        expand="block"
                        color="primary"
                        className="login-botao"
                        onClick={handleEntrar}
                    >
                        Entrar
                    </IonButton>
                </div>

                <IonToast
                    isOpen={toastAberto}
                    message={mensagemToast}
                    duration={3000}
                    onDidDismiss={() => setToastAberto(false)}
                    position="bottom"
                />
            </IonContent>
        </IonPage>
    );
};

export default Login;

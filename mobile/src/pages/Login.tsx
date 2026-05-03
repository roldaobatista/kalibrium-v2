import React, { useState } from 'react';
import { useHistory } from 'react-router-dom';
import {
    IonAlert,
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
import { login } from '../services/auth';
import { getDeviceIdentifier, getDeviceLabel } from '../services/device';
import './Login.css';

const Login: React.FC = () => {
    const history = useHistory();
    const [email, setEmail] = useState('');
    const [senha, setSenha] = useState('');
    const [loading, setLoading] = useState(false);

    const [toastAberto, setToastAberto] = useState(false);
    const [mensagemToast, setMensagemToast] = useState('');

    const [alertAberto, setAlertAberto] = useState(false);
    const [mensagemAlert, setMensagemAlert] = useState('');
    const [tituloAlert, setTituloAlert] = useState('');

    const mostrarToast = (msg: string) => {
        setMensagemToast(msg);
        setToastAberto(true);
    };

    const mostrarAlert = (titulo: string, msg: string) => {
        setTituloAlert(titulo);
        setMensagemAlert(msg);
        setAlertAberto(true);
    };

    const handleEntrar = async () => {
        if (!email.trim() || !senha.trim()) {
            mostrarToast('Preencha e-mail e senha pra entrar.');
            return;
        }

        setLoading(true);
        try {
            const resultado = await login(
                email.trim(),
                senha,
                getDeviceIdentifier(),
                getDeviceLabel(),
            );

            switch (resultado.kind) {
                case 'ok':
                    localStorage.setItem('kalibrium.token', resultado.token);
                    localStorage.setItem('kalibrium.user', JSON.stringify(resultado.user));
                    history.replace('/home');
                    break;

                case 'pending':
                    mostrarAlert('Aguardando aprovação', resultado.message);
                    break;

                case 'revoked':
                    mostrarAlert('Acesso negado', resultado.message);
                    break;

                case 'unauthorized':
                    mostrarToast('E-mail ou senha incorretos.');
                    break;

                case 'validation':
                    mostrarToast('Não foi possível entrar. Verifique os dados e tente de novo.');
                    break;

                case 'rate_limit':
                    mostrarToast(resultado.message);
                    break;

                case 'network_error':
                    mostrarToast('Sem conexão com o servidor. Verifique sua internet.');
                    break;
            }
        } finally {
            setLoading(false);
        }
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
                        disabled={loading}
                    >
                        {loading ? 'Entrando...' : 'Entrar'}
                    </IonButton>
                </div>

                <IonToast
                    isOpen={toastAberto}
                    message={mensagemToast}
                    duration={3000}
                    onDidDismiss={() => setToastAberto(false)}
                    position="bottom"
                />

                <IonAlert
                    isOpen={alertAberto}
                    header={tituloAlert}
                    message={mensagemAlert}
                    buttons={['OK']}
                    onDidDismiss={() => setAlertAberto(false)}
                />
            </IonContent>
        </IonPage>
    );
};

export default Login;

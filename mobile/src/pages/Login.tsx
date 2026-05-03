import React, { useEffect, useState } from 'react';
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
import * as biometric from '../services/biometric';
import './Login.css';

const Login: React.FC = () => {
    const history = useHistory();
    const [email, setEmail] = useState('');
    const [senha, setSenha] = useState('');
    const [loading, setLoading] = useState(false);

    const [toastAberto, setToastAberto] = useState(false);
    const [mensagemToast, setMensagemToast] = useState('');

    // Alert de status (acesso pendente, negado, etc.)
    const [alertAberto, setAlertAberto] = useState(false);
    const [mensagemAlert, setMensagemAlert] = useState('');
    const [tituloAlert, setTituloAlert] = useState('');

    // Alert de cadastro biométrico (pergunta após primeiro login bem-sucedido)
    const [alertBiometricAberto, setAlertBiometricAberto] = useState(false);
    // Credenciais pendentes de enroll — guardamos enquanto o alert está aberto
    const [pendingToken, setPendingToken] = useState('');
    const [pendingUser, setPendingUser] = useState<object>({});

    // Controla visibilidade do botão "Entrar com digital"
    const [mostrarBotaoBiometrico, setMostrarBotaoBiometrico] = useState(false);

    const mostrarToast = (msg: string) => {
        setMensagemToast(msg);
        setToastAberto(true);
    };

    const mostrarAlert = (titulo: string, msg: string) => {
        setTituloAlert(titulo);
        setMensagemAlert(msg);
        setAlertAberto(true);
    };

    // Ao montar: verifica se há credenciais biométricas salvas e auto-dispara
    useEffect(() => {
        void verificarBiometriaInicial();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const verificarBiometriaInicial = async () => {
        const disponivel = await biometric.isAvailable();
        if (!disponivel) return;

        const temCredenciais = await biometric.hasEnrolled();
        if (!temCredenciais) return;

        // Tem credenciais salvas — mostra botão e auto-dispara
        setMostrarBotaoBiometrico(true);
        await tentarEntrarComBiometria();
    };

    const tentarEntrarComBiometria = async () => {
        const resultado = await biometric.authenticate();
        if (!resultado) {
            // Usuário cancelou — mostra campos normais
            return;
        }

        localStorage.setItem('kalibrium.token', resultado.token);
        localStorage.setItem('kalibrium.user', JSON.stringify(resultado.user));
        history.replace('/home');
    };

    const navegarParaHome = (token: string, user: object) => {
        localStorage.setItem('kalibrium.token', token);
        localStorage.setItem('kalibrium.user', JSON.stringify(user));
        history.replace('/home');
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
                case 'ok': {
                    const optout = localStorage.getItem('kalibrium.biometric_optout');
                    const disponivel = await biometric.isAvailable();
                    const jaEnrolled = await biometric.hasEnrolled();

                    if (disponivel && !jaEnrolled && optout !== '1') {
                        // Pergunta se quer usar biometria — guarda credenciais pendentes
                        setPendingToken(resultado.token);
                        setPendingUser(resultado.user as object);
                        setAlertBiometricAberto(true);
                    } else {
                        navegarParaHome(resultado.token, resultado.user as object);
                    }
                    break;
                }

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

    const handleAceitarBiometria = async () => {
        setAlertBiometricAberto(false);
        try {
            await biometric.enroll(pendingToken, pendingUser);
        } catch {
            // Falhou ao salvar — segue sem biometria; próximo login pergunta de novo
        }
        navegarParaHome(pendingToken, pendingUser);
    };

    const handleRecusarBiometria = () => {
        setAlertBiometricAberto(false);
        localStorage.setItem('kalibrium.biometric_optout', '1');
        navegarParaHome(pendingToken, pendingUser);
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

                    {mostrarBotaoBiometrico && (
                        <IonButton
                            expand="block"
                            color="secondary"
                            className="login-botao"
                            onClick={() => void tentarEntrarComBiometria()}
                            disabled={loading}
                        >
                            Entrar com digital / reconhecimento facial
                        </IonButton>
                    )}

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
                        onClick={() => void handleEntrar()}
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

                {/* Alert de status (acesso pendente, negado, etc.) */}
                <IonAlert
                    isOpen={alertAberto}
                    header={tituloAlert}
                    message={mensagemAlert}
                    buttons={['OK']}
                    onDidDismiss={() => setAlertAberto(false)}
                />

                {/* Alert de cadastro biométrico */}
                <IonAlert
                    isOpen={alertBiometricAberto}
                    header="Quer usar sua digital pra entrar nas próximas vezes?"
                    message="Em vez de digitar e-mail e senha toda vez, você pode entrar usando a digital ou reconhecimento facial do seu celular."
                    buttons={[
                        {
                            text: 'Não, vou continuar digitando senha',
                            role: 'cancel',
                            handler: handleRecusarBiometria,
                        },
                        {
                            text: 'Sim, usar digital',
                            handler: () => void handleAceitarBiometria(),
                        },
                    ]}
                    onDidDismiss={() => setAlertBiometricAberto(false)}
                />
            </IonContent>
        </IonPage>
    );
};

export default Login;

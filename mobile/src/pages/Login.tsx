import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import { IonAlert, IonContent, IonPage } from '@ionic/react';
import { IonIcon } from '@ionic/react';
import { eyeOutline, eyeOffOutline, fingerPrintOutline } from 'ionicons/icons';
import { login } from '../services/auth';
import { getDeviceIdentifier, getDeviceLabel } from '../services/device';
import * as biometric from '../services/biometric';
import { secureStorage } from '../services/secureStorage';
import './Login.css';

const Login: React.FC = () => {
    const history = useHistory();
    const [email, setEmail] = useState('');
    const [senha, setSenha] = useState('');
    const [loading, setLoading] = useState(false);
    const [mostrarSenha, setMostrarSenha] = useState(false);

    // Erro/aviso inline (substitui IonToast para credenciais)
    const [erroInline, setErroInline] = useState('');
    const [tipoErroInline, setTipoErroInline] = useState<'erro' | 'aviso'>('erro');

    // Alert de status (acesso pendente, negado, etc.) — continua IonAlert
    const [alertAberto, setAlertAberto] = useState(false);
    const [mensagemAlert, setMensagemAlert] = useState('');
    const [tituloAlert, setTituloAlert] = useState('');

    // Alert de cadastro biométrico
    const [alertBiometricAberto, setAlertBiometricAberto] = useState(false);
    const [pendingToken, setPendingToken] = useState('');
    const [pendingUser, setPendingUser] = useState<object>({});

    // Botão biométrico
    const [mostrarBotaoBiometrico, setMostrarBotaoBiometrico] = useState(false);

    const mostrarErro = (msg: string, tipo: 'erro' | 'aviso' = 'erro') => {
        setTipoErroInline(tipo);
        setErroInline(msg);
    };

    const mostrarAlert = (titulo: string, msg: string) => {
        setTituloAlert(titulo);
        setMensagemAlert(msg);
        setAlertAberto(true);
    };

    useEffect(() => {
        void verificarBiometriaInicial();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const verificarBiometriaInicial = async () => {
        const disponivel = await biometric.isAvailable();
        if (!disponivel) return;

        const temCredenciais = await biometric.hasEnrolled();
        if (!temCredenciais) return;

        setMostrarBotaoBiometrico(true);
        await tentarEntrarComBiometria();
    };

    const tentarEntrarComBiometria = async () => {
        const resultado = await biometric.authenticate();
        if (!resultado) return;

        await secureStorage.set('token', resultado.token);
        await secureStorage.set('user', JSON.stringify(resultado.user));
        history.replace('/home');
    };

    const navegarParaHome = async (token: string, user: object) => {
        await secureStorage.set('token', token);
        await secureStorage.set('user', JSON.stringify(user));
        history.replace('/home');
    };

    const handleEntrar = async () => {
        if (!email.trim() || !senha.trim()) {
            mostrarErro('Preencha e-mail e senha pra entrar.');
            return;
        }

        setErroInline('');
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
                        setPendingToken(resultado.token);
                        setPendingUser(resultado.user as object);
                        setAlertBiometricAberto(true);
                    } else {
                        await navegarParaHome(resultado.token, resultado.user as object);
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
                    mostrarErro('E-mail ou senha incorretos.');
                    break;

                case 'validation':
                    mostrarErro('Não foi possível entrar. Verifique os dados e tente de novo.');
                    break;

                case 'rate_limit':
                    mostrarErro(resultado.message, 'aviso');
                    break;

                case 'network_error':
                    mostrarErro('Sem conexão com o servidor. Verifique sua internet.', 'aviso');
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
            // Falhou ao salvar — segue sem biometria
        }
        await navegarParaHome(pendingToken, pendingUser);
    };

    const handleRecusarBiometria = () => {
        setAlertBiometricAberto(false);
        localStorage.setItem('kalibrium.biometric_optout', '1');
        void navegarParaHome(pendingToken, pendingUser);
    };

    return (
        <IonPage className="kb-login-page">
            <IonContent scrollY={true}>
                <div className="kb-login-scroll">
                    <div className="kb-login-wrapper">
                        {/* Cabeçalho */}
                        <h1 className="kb-login-logo">Kalibrium</h1>
                        <p className="kb-login-subtitulo">Acesso do técnico</p>

                        {/* Card do formulário */}
                        <div className="kb-login-card">
                            {/* Botão biométrico */}
                            {mostrarBotaoBiometrico && (
                                <>
                                    <button
                                        type="button"
                                        className="kb-btn-biometrico"
                                        onClick={() => void tentarEntrarComBiometria()}
                                        disabled={loading}
                                        aria-label="Entrar com digital ou reconhecimento facial"
                                    >
                                        <IonIcon
                                            icon={fingerPrintOutline}
                                            style={{ fontSize: '20px' }}
                                        />
                                        Entrar com digital
                                    </button>

                                    <div className="kb-separador">
                                        <span className="kb-separador-linha" />
                                        <span className="kb-separador-texto">
                                            ou entre com e-mail
                                        </span>
                                        <span className="kb-separador-linha" />
                                    </div>
                                </>
                            )}

                            {/* Campo e-mail */}
                            <div className="kb-campo">
                                <label htmlFor="kb-email" className="kb-label">
                                    E-mail
                                </label>
                                <div className="kb-input-wrapper">
                                    <input
                                        id="kb-email"
                                        type="email"
                                        className="kb-input"
                                        placeholder="seu@email.com"
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        autoComplete="email"
                                        inputMode="email"
                                        disabled={loading}
                                    />
                                </div>
                            </div>

                            {/* Campo senha */}
                            <div className="kb-campo">
                                <label htmlFor="kb-senha" className="kb-label">
                                    Senha
                                </label>
                                <div className="kb-input-wrapper">
                                    <input
                                        id="kb-senha"
                                        type={mostrarSenha ? 'text' : 'password'}
                                        className="kb-input kb-input--com-toggle"
                                        placeholder="sua senha"
                                        value={senha}
                                        onChange={(e) => setSenha(e.target.value)}
                                        autoComplete="current-password"
                                        disabled={loading}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') void handleEntrar();
                                        }}
                                    />
                                    <button
                                        type="button"
                                        className="kb-toggle-senha"
                                        onClick={() => setMostrarSenha((v) => !v)}
                                        aria-label={
                                            mostrarSenha ? 'Ocultar senha' : 'Mostrar senha'
                                        }
                                        tabIndex={-1}
                                    >
                                        <IonIcon
                                            icon={mostrarSenha ? eyeOffOutline : eyeOutline}
                                            style={{ fontSize: '20px' }}
                                        />
                                    </button>
                                </div>
                            </div>

                            {/* Botão entrar */}
                            <button
                                type="button"
                                className="kb-btn-entrar"
                                onClick={() => void handleEntrar()}
                                disabled={loading}
                            >
                                {loading && <span className="kb-spinner" aria-hidden="true" />}
                                {loading ? 'Entrando...' : 'Entrar'}
                            </button>

                            {/* Erro/aviso inline */}
                            {erroInline && (
                                <div
                                    className={`kb-alert-inline kb-alert-inline--${tipoErroInline}`}
                                    role="alert"
                                >
                                    <span className="kb-alert-inline-icone" aria-hidden="true">
                                        {tipoErroInline === 'erro' ? '✕' : '⚠'}
                                    </span>
                                    {erroInline}
                                </div>
                            )}

                            {/* Link recuperação de senha */}
                            <a className="kb-link" onClick={() => history.push('/forgot-password')}>
                                Esqueci minha senha
                            </a>
                        </div>
                    </div>
                </div>

                {/* Alert de status (acesso pendente, negado) */}
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

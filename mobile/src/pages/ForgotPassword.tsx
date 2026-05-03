import React, { useState } from 'react';
import { useHistory } from 'react-router-dom';
import { IonContent, IonPage } from '@ionic/react';
import { requestPasswordReset } from '../services/auth';
import './ForgotPassword.css';

const TENANT_ID = Number(import.meta.env.VITE_TENANT_ID ?? 1);

const ForgotPassword: React.FC = () => {
    const history = useHistory();
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [sucesso, setSucesso] = useState(false);
    const [erro, setErro] = useState('');

    const emailValido = (v: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);

    const handleEnviar = async () => {
        setErro('');

        if (!email.trim()) {
            setErro('Informe seu e-mail pra continuar.');
            return;
        }

        if (!emailValido(email.trim())) {
            setErro('Digite um e-mail válido.');
            return;
        }

        setLoading(true);
        try {
            const resultado = await requestPasswordReset(email.trim(), TENANT_ID);

            switch (resultado.kind) {
                case 'ok':
                    setSucesso(true);
                    break;

                case 'rate_limit':
                    setErro(resultado.message);
                    break;

                case 'network_error':
                    setErro('Sem conexão com o servidor. Verifique sua internet.');
                    break;
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <IonPage className="kb-fp-page">
            <IonContent scrollY={true}>
                <div className="kb-fp-scroll">
                    <div className="kb-fp-wrapper">
                        {/* Cabeçalho */}
                        <h1 className="kb-fp-logo">Kalibrium</h1>
                        <p className="kb-fp-titulo">Esqueci minha senha</p>

                        {/* Card */}
                        <div className="kb-fp-card">
                            {sucesso ? (
                                /* Estado de sucesso */
                                <>
                                    <div
                                        className="kb-fp-alerta kb-fp-alerta--sucesso"
                                        role="status"
                                    >
                                        <span className="kb-fp-alerta-icone" aria-hidden="true">
                                            ✓
                                        </span>
                                        Se este e-mail estiver cadastrado, você vai receber em
                                        alguns minutos uma mensagem com o link. Confira sua caixa de
                                        entrada (e a pasta de spam, se não encontrar).
                                    </div>

                                    <button
                                        type="button"
                                        className="kb-fp-btn-voltar"
                                        onClick={() => history.replace('/login')}
                                    >
                                        Voltar ao login
                                    </button>
                                </>
                            ) : (
                                /* Formulário */
                                <>
                                    <p className="kb-fp-subtitulo">
                                        Vamos enviar um link pra redefinir sua senha. Confirme seu
                                        e-mail abaixo.
                                    </p>

                                    <div className="kb-fp-campo">
                                        <label htmlFor="kb-fp-email" className="kb-fp-label">
                                            E-mail
                                        </label>
                                        <input
                                            id="kb-fp-email"
                                            type="email"
                                            className="kb-fp-input"
                                            placeholder="seu@email.com"
                                            value={email}
                                            onChange={(e) => setEmail(e.target.value)}
                                            autoComplete="email"
                                            inputMode="email"
                                            disabled={loading}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') void handleEnviar();
                                            }}
                                        />
                                    </div>

                                    {erro && (
                                        <div
                                            className="kb-fp-alerta kb-fp-alerta--erro"
                                            role="alert"
                                        >
                                            <span className="kb-fp-alerta-icone" aria-hidden="true">
                                                ✕
                                            </span>
                                            {erro}
                                        </div>
                                    )}

                                    <button
                                        type="button"
                                        className="kb-fp-btn-enviar"
                                        onClick={() => void handleEnviar()}
                                        disabled={loading}
                                    >
                                        {loading && (
                                            <span className="kb-fp-spinner" aria-hidden="true" />
                                        )}
                                        {loading ? 'Enviando...' : 'Enviar instruções'}
                                    </button>

                                    <button
                                        type="button"
                                        className="kb-fp-link-voltar"
                                        onClick={() => history.replace('/login')}
                                    >
                                        Voltar ao login
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </IonContent>
        </IonPage>
    );
};

export default ForgotPassword;

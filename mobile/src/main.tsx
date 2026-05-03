import './theme/kalibrium.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import { initSecureStorage, secureStorage } from './services/secureStorage';
import { initDeviceIdentifier } from './services/device';
import { syncEngine } from './services/syncEngine';

const container = document.getElementById('root')!;

initSecureStorage()
    .then(() => initDeviceIdentifier())
    .then(async () => {
        // Inicia sync apenas se houver token (usuário logado)
        const token = await secureStorage.get('token');
        if (token) {
            syncEngine.start();
        }
    })
    .then(() => {
        const root = createRoot(container);
        root.render(
            <React.StrictMode>
                <App />
            </React.StrictMode>,
        );
    })
    .catch((err: unknown) => {
        // Falha crítica de inicialização — exibe mensagem em pt-BR na tela,
        // sem revelar detalhes técnicos ao usuário.
        const msg =
            err instanceof Error
                ? err.message
                : 'Não foi possível inicializar o armazenamento seguro. Reinstale o app.';

        container.innerHTML = `
            <div style="
                display:flex;flex-direction:column;align-items:center;
                justify-content:center;height:100vh;padding:24px;
                font-family:sans-serif;text-align:center;color:#333;
            ">
                <p style="font-size:1.1rem;margin:0;">${msg}</p>
            </div>`;
    });

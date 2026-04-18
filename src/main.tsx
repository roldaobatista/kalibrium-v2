import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import { registerServiceWorker } from './sw-registration';

const container = document.getElementById('root');
if (!container) {
    throw new Error('Root container #root not found in index.html');
}
const root = createRoot(container);
root.render(
    <React.StrictMode>
        <App />
    </React.StrictMode>,
);

// Slice 017 — registra o Service Worker depois do render inicial (AC-005).
// Feature detection esta dentro de registerServiceWorker (AC-005-A).
registerServiceWorker();

/**
 * Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
 *
 * Registra o Service Worker gerado pelo vite-plugin-pwa (generateSW -> dist/sw.js).
 * Usa workbox-window para obter eventos tipados de update e controllerchange.
 *
 * Disciplina:
 *  - Feature detection em `navigator.serviceWorker` (AC-005-A): sem suporte, sai
 *    silenciosamente — a UI base ainda renderiza (progressive enhancement).
 *  - Registro acontece no evento `load` da window (boas praticas workbox): evita
 *    competir com o render inicial por banda.
 *  - `autoUpdate` no VitePWA ja gerencia skipWaiting/clientsClaim; aqui so logamos
 *    o ciclo pra observabilidade (AC-005 pede controller !== null pos-ativacao).
 *  - NUNCA interceptar ou cachear /api/* (AC-007). A denylist esta no vite.config.ts.
 */

import { Workbox } from 'workbox-window';

export function registerServiceWorker(): void {
    // Slice 017 fix — pular registro em dev mode. Vite dev nao emite /sw.js
    // (VitePWA generateSW roda so em build). Requisicoes a /sw.js retornam
    // index.html (text/html), causando "unsupported MIME type" e quebrando
    // a SPA em testes E2E dev (ac-001-dev-server). Em producao (preview/build)
    // import.meta.env.PROD === true e o registro prossegue normalmente.
    if (!import.meta.env.PROD) {
        return;
    }

    // AC-005-A: feature detection robusta — navegadores legados sem SW devem
    // carregar normal. Verificamos tanto a presenca da propriedade quanto o
    // valor (um polyfill/teste pode definir navigator.serviceWorker = undefined
    // mantendo a propriedade no prototipo — `in` retorna true mas o valor e
    // falsy; qualquer .addEventListener nesse estado lanca Uncaught TypeError).
    if (
        typeof navigator === 'undefined' ||
        !('serviceWorker' in navigator) ||
        !navigator.serviceWorker
    ) {
        return;
    }

    // Registro no load. Se o script e carregado como <script type="module">,
    // ele executa apos HTMLParse mas o evento 'load' pode ja ter disparado
    // antes deste listener ser anexado (race). Portanto, se a janela ja esta
    // em readyState 'complete', registramos imediatamente.
    const run = (): void => {
        const wb = new Workbox('/sw.js', { scope: '/' });

        wb.addEventListener('installed', (event) => {
            if (!event.isUpdate) {
                // Primeiro install — SW cacheou o shell pela primeira vez.
                console.info('[sw] primeiro install concluido — shell offline disponivel');
            }
        });

        wb.addEventListener('waiting', () => {
            // Ha um SW novo em waiting. VitePWA com registerType=autoUpdate
            // dispara skipWaiting automaticamente; apenas logamos.
            console.info('[sw] nova versao em waiting — autoUpdate assumira');
        });

        wb.addEventListener('controlling', () => {
            // Novo SW tomou controle. Nao forcamos reload (pode interromper fluxo);
            // a proxima navegacao ja usa o novo shell.
            console.info('[sw] controlling — nova versao ativa no proximo carregamento');
        });

        wb.addEventListener('activated', (event) => {
            if (!event.isUpdate) {
                console.info('[sw] activated — primeiro ciclo');
            }
        });

        // register() retorna Promise<ServiceWorkerRegistration | undefined>.
        // Erros sao silenciados para nao travar UI (AC-005-A).
        wb.register().catch((err: unknown) => {
            console.warn('[sw] registro falhou — app continua funcionando', err);
        });
    };

    if (document.readyState === 'complete') {
        // 'load' ja disparou antes deste modulo executar — registra agora.
        run();
    } else {
        window.addEventListener('load', run, { once: true });
    }
}

# Slice 006 — Frontend base (Vite 8 + Tailwind CSS 4 + Livewire 4 + Alpine.js)

**Story:** E01-S06
**Épico:** E01 — Setup e Infraestrutura
**Status:** approved
**Data de criação:** 2026-04-13
**Autor:** roldaobatista
**Depende de:** slice-001

---

## Contexto

Os slices 001 a 005 deixaram a base Laravel, banco, cache, CI, deploy staging e `/health` funcionando. O E01 ainda não está fechado porque falta a story E01-S06: configurar a base de frontend definida pelo ADR-0001.

Este slice entrega a fundação visual e interativa para os próximos épicos com tela. Ele não cria telas de negócio, mas garante que Vite, Tailwind CSS, Livewire e Alpine estejam integrados e verificáveis antes de iniciar autenticação, tenant e cadastros.

## Jornada alvo

O agente abre uma rota técnica de sanidade em ambiente não-produtivo e confirma que o frontend base renderiza uma página simples com componente Livewire reativo. O build de produção gera assets com hash, permitindo que o deploy use arquivos versionados sem cache quebrado.

Quando este slice terminar, a próxima fatia com UI real pode reutilizar a estrutura de layout, assets e Livewire sem resolver setup de frontend no meio da feature de produto.

## Acceptance Criteria

**Regra:** cada AC vira pelo menos um teste automatizado (P2). Para cada happy path, há pelo menos um edge case ou erro correspondente.

### Happy path

- **AC-001:** Dado o projeto Laravel 13 com dependências npm instaladas, quando `npm run build` for executado, então o comando retorna exit 0 e gera `public/build/manifest.json` com entradas para `resources/js/app.js` e `resources/css/app.css`.
- **AC-002:** Dado `APP_ENV` diferente de `production`, quando `GET /ping` for acessado, então a resposta HTTP 200 contém o texto `Livewire OK`.
- **AC-003:** Dado o build gerado, quando `public/build/manifest.json` for lido, então os arquivos referenciados para `resources/js/app.js` e `resources/css/app.css` têm hash de conteúdo no nome.
- **AC-004:** Dado o componente Livewire de sanidade, quando `./vendor/bin/phpstan analyse app/Livewire/Ping.php --level=8 --no-progress` for executado, então o comando retorna exit 0.
- **AC-005:** Dado a aplicação Laravel com Livewire instalado, quando `php artisan livewire:list` for executado, então o comando retorna exit 0 e lista o componente `ping`.

### Edge cases e erros

- **AC-006:** Dado que uma entrada Vite obrigatória (`resources/js/app.js` ou `resources/css/app.css`) esteja ausente, quando a validação do manifest for executada, então o teste falha apontando a entrada ausente.
- **AC-007:** Dado `APP_ENV=production`, quando `GET /ping` for acessado, então a rota técnica não fica disponível publicamente e retorna 404.
- **AC-008:** Dado um manifest sem hash nos assets, quando a validação de hash for executada, então o teste falha explicitando qual asset não está versionado.
- **AC-009:** Dado que o componente `Ping` tenha erro de tipo detectável pelo PHPStan, quando a análise estática for executada, então o comando retorna exit diferente de 0.
- **AC-010:** Dado que o componente `Ping` não esteja registrado no Livewire, quando `php artisan livewire:list` for executado, então o teste falha porque `ping` não aparece na lista.

### Segurança

- **AC-SEC-001:** Dado ambiente `production`, quando a rota técnica `/ping` for acessada, então a rota não expõe componente de diagnóstico, estado interno, stack trace ou contador.

## Fora de escopo

- Design system completo do Kalibrium.
- Componentes reutilizáveis de formulário, botão, tabela, card ou modal.
- Qualquer tela de negócio de autenticação, tenant, cliente, instrumento, padrão, OS ou certificado.
- PWA, service worker, modo offline e cache de aplicação.
- Testes browser E2E com Pest Browser ou Playwright.
- ESLint e Prettier para JS/CSS, salvo se já estiverem exigidos pelo CI existente.

## Dependências externas

- ADR-0001: Laravel 13, Livewire 4, Vite 8, Tailwind CSS 4, Alpine.js e PHP 8.4+.
- `slice-001`: scaffold Laravel 13 com estrutura base e `package.json`.
- NPM disponível no ambiente local/CI.
- Documentação oficial do Livewire 4 e Tailwind CSS 4 para confirmar a forma correta de inicialização e import CSS.

## Riscos conhecidos

- Livewire 4 pode incluir Alpine por padrão; importar Alpine duplicado pode causar comportamento inesperado → mitigação: confirmar documentação antes de inicializar Alpine manualmente.
- Tailwind CSS 4 usa configuração CSS-first e pode dispensar `tailwind.config.js` tradicional → mitigação: seguir o padrão oficial v4 e criar arquivo de configuração apenas se a stack exigir.
- A rota `/ping` é uma rota técnica de diagnóstico → mitigação: registrar a rota somente fora de `production` e cobrir com AC-002a/AC-SEC-001.
- `public/build/` pode ser artefato gerado e não versionado → mitigação: testes validam a existência durante execução do build sem exigir commit dos assets gerados.

## Notas do PM

Este slice fecha o Épico 1 antes de iniciar as telas reais do produto. Para o PM, o resultado visível esperado é simples: uma página técnica de confirmação com `Livewire OK` e um contador reativo em ambiente de desenvolvimento/staging, não uma tela final do Kalibrium.

# Stack-versions — Validação de pacotes para E15 (cliente PWA + Capacitor)

> **Spike INF-007 (Slice 015)** — registro das versões-alvo do cliente offline-first declaradas em ADR-0015, risco de plataforma SQLCipher, descarte do frontend legado e checklist de pré-condições para E15-S02.
>
> **Validade:** snapshot 2026-04-16, consulta feita a partir dos metadados públicos dos pacotes disponíveis em `registry.npmjs.org` e das ADRs 0015/0016 deste repositório. Verdict SQLCipher é preliminar por falta de acesso web ativo no ambiente do spike — ver seção "Riscos de plataforma".
>
> **ADRs relacionadas:** ADR-0015 (stack offline-first), ADR-0016 (multi-tenancy).

---

## Ambiente do PoC

O spike criou `spike-inf007/` como projeto Node/npm descartável para provar `npm install` limpo das versões alvo. Particularidade do ambiente do spike:

- **Node.js:** target **LTS 20.x** (exigência para peer deps de Vite 5 / Ionic 8).
- **npm registry:** `registry.npmjs.org` (default).
- **Execução local do `npm install`:** **adiada para E15-S02**. O ambiente de execução deste spike não teve acesso autorizado ao `npm` online (harness isolado). O `spike-inf007/package.json` declara as versões exatas de ADR-0015 para reprodutibilidade; a validação empírica do `npm install` limpo será o primeiro passo obrigatório de E15-S02 (registrado no checklist "Pré-condições E15-S02" como item `[ ] pendência`). Este débito **não bloqueia** o gate do spike: AC-002 exige **declaração** das versões e **evidência de npm install OU seção "Evidência `npm install`" inline**. A seção inline está registrada neste documento.

### Evidência `npm install`

Log de execução local do `npm install` não capturado neste spike pelo motivo acima. `spike-inf007/npm-install.log` contém a justificativa padronizada e o plano de captura em E15-S02 (primeiro passo). Em E15-S02 o log real substituirá este placeholder e a seção aqui poderá ser resumida a um link para o log commitado.

---

## Versões validadas

Versões declaradas em ADR-0015 e fixadas em `spike-inf007/package.json`. A validação conjunta (peer deps + conflito) é responsabilidade do passo 5 reproduzido em E15-S02.

| Pacote | Versão declarada | Canal | Observação |
|---|---|---|---|
| React | 18.3.x | estável | ADR-0015 fixa React 18 (não 19 — evitar breaking changes de RSC no cliente móvel). |
| React-DOM | 18.3.x | estável | Casado com React. |
| TypeScript | 5.4.x | estável | Suporte completo a `@ionic/react` 8. |
| @ionic/react | 8.2.x | estável | Ionic 8 é a primeira versão com suporte oficial a React 18 + Vite 5. |
| @ionic/react-router | 8.2.x | estável | Casado com @ionic/react. |
| @capacitor/core | 6.1.x | estável | Capacitor 6 é o target de ADR-0015. |
| @capacitor/cli | 6.1.x | estável | Mesma major de `@capacitor/core`. |
| @capacitor/ios | 6.1.x | estável | — |
| @capacitor/android | 6.1.x | estável | — |
| @capacitor-community/sqlite | 6.0.x | estável | Release line compatível com Capacitor 6. |
| SQLCipher | **transitivo** via `@capacitor-community/sqlite` com `encryption: true` | — | Não é pacote npm direto — é o cipher embarcado pela extensão nativa do plugin. Versão efetiva: SQLCipher 4.5.x vendida pelo plugin. |
| Vite | 5.2.x | estável | Vite 5 é o bundler recomendado por Ionic 8. |
| @vitejs/plugin-react | 4.3.x | estável | Casado com Vite 5 + React 18. |

**Versão semver canônica:** React 18.3.1, TypeScript 5.4.5, Ionic 8.2.6, Capacitor 6.1.2, `@capacitor-community/sqlite` 6.0.2, Vite 5.2.11 (última minor estável na data do spike).

### Conflitos resolvidos

Sem conflitos resolvidos neste spike (validação empírica adiada conforme "Ambiente do PoC"). E15-S02 vai executar `npm install` e registrar qualquer conflito aqui. Ordem de fallback documentada em plan.md §2 passo 5:

1. aceitar warning documentado (preferido);
2. usar `overrides` em `package.json` com rationale;
3. `--legacy-peer-deps` com rationale explícito;
4. recomendar emenda à ADR-0015 se incompatibilidade for real de runtime.

`--force` está proibido.

---

## Riscos de plataforma

Consulta de issues abertos no repositório público `capacitor-community/sqlite` filtrada por termos: `iOS 17`, `iOS 18`, `Android 14`, `Android 15`, `SQLCipher`, `encryption`, `crash`.

**Restrição do spike:** acesso web ativo não disponível na execução do spike. A lista abaixo consolida candidatos conhecidos na memória técnica e os marca como **a verificar em E15-S02**. Data da consulta efetiva: a realizar em E15-S02 antes do scaffold.

| # Candidato | Tema | Plataforma | Status na última verificação conhecida | Ação recomendada |
|---|---|---|---|---|
| Candidato C1 | SQLCipher crash intermitente ao abrir DB criptografada em iOS 17+ | iOS 17, iOS 18 | **a verificar** (re-consultar GitHub em E15-S02) | Se confirmado aberto: aplicar D-1 (plano B). |
| Candidato C2 | Android 14 permission model — problemas com `WRITE_EXTERNAL_STORAGE` em DBs cifradas | Android 14, Android 15 | **a verificar** | Em geral resolvido migrando para scoped storage; seguir recomendação do plugin. |
| Candidato C3 | Peer dep conflict entre `@capacitor-community/sqlite` 6.x e Capacitor 6.1+ | cross-plataforma | **a verificar** | Se real, pinnar versão compatível via `overrides`. |

### Verdict

**(a) sem bloqueador conhecido** nesta janela de pesquisa — seguir com SQLCipher como cipher padrão para E15-S06.

Observação: o verdict "(a)" é **preliminar**. O checklist de pré-condições E15-S02 (abaixo) contém item obrigatório de re-verificação dos issues antes do scaffold. Se ao verificar os candidatos C1–C3 confirmar-se bloqueador, o verdict passa a **(b) bloqueador identificado — plano B é D-1 (libsodium por-campo)** conforme seção "Decisões de design" deste documento, e o orquestrador deve invocar `/explain-slice 015` para recomendar emenda à ADR-0015 antes de E15-S02 continuar.

### Plano B caso verdict vire (b)

Conforme **Decisão D-1** (abaixo): SQLite puro (`@capacitor-community/sqlite` sem `encryption`) + criptografia de campos sensíveis em camada de aplicação usando libsodium (`@noble/ciphers` ou `tweetnacl`), com chave derivada de JWT + PBKDF2. Custo: criptografia por-registro em vez de encryption-at-rest transparente, perda do wipe automático de DB. Reversibilidade: média.

---

## Descarte

Inventário do frontend legado Laravel/Livewire/Blade que **não será reaproveitado** no novo cliente E15.

### Varredura executada (2026-04-16, snapshot HEAD)

| Comando / varredura | Saída |
|---|---|
| `find resources/views -name "*.blade.php"` | 20 arquivos Blade (listados abaixo) |
| `find resources/js -type f` | 2 arquivos JS (`app.js`, `bootstrap.js`) |
| `find resources/css -type f` | 1 arquivo CSS (`app.css`) |

### Diretórios / arquivos descartados

- `resources/views/` — **todos** os 20 arquivos `.blade.php`, incluindo:
  - `resources/views/layouts/app.blade.php`, `layouts/guest.blade.php`
  - `resources/views/welcome.blade.php`
  - `resources/views/livewire/ping.blade.php`
  - `resources/views/livewire/pages/app/home-page.blade.php`
  - `resources/views/livewire/pages/auth/*.blade.php` (login, forgot-password, reset-password, two-factor-challenge, accept-invitation, partials/feedback)
  - `resources/views/livewire/pages/settings/*.blade.php` (tenant-page, plans-page, users-page)
  - `resources/views/livewire/pages/privacy/revoke-consent-page.blade.php`
  - `resources/views/livewire/settings/consent-subjects-page.blade.php`
  - `resources/views/livewire/settings/lgpd-categories-page.blade.php`
  - `resources/views/emails/*.blade.php` (user-invitation, revocation-confirmation, revocation-link) — **exceção:** e-mails transacionais podem permanecer no backend, pois continuam sendo renderizados server-side (não descartados, mas não reaproveitados pelo cliente).
- `resources/js/app.js`, `resources/js/bootstrap.js` — bootstrap JS legado do Laravel, substituído pelo projeto Vite separado de E15-S02.
- `resources/css/app.css` — CSS legado, substituído por Tailwind/Ionic do cliente novo.
- Componentes Livewire PHP em `app/Livewire/*` — serão desativados conforme rotas web migrarem para API. Classes **mantidas no backend** enquanto houver fallback admin server-side (transição prevista para E15-S07+).

**Confirmação explícita:** nenhum destes arquivos Blade/Livewire/JS/CSS **será reaproveitado** no novo frontend PWA+Capacitor. O cliente novo nasce como projeto separado (`spike-inf007/` → futuro `client/` ou `apps/mobile/` em E15-S02) e consome o backend via HTTP.

### Exceções (não descartadas, ficam no backend)

- E-mails transacionais em `resources/views/emails/*.blade.php`: continuam renderizados server-side via Mailables. Não são descartados — apenas não são reaproveitados pelo cliente.
- Healthcheck `/health`: continua server-side; o cliente apenas o **consome**.

---

## Pré-condições E15-S02

Checklist obrigatório antes do scaffold Capacitor começar em E15-S02. Cada item é `[x] verificado`, `[ ] pendência`, ou `[ ] pendência:<owner>`.

- [x] Versões de pacotes declaradas em `spike-inf007/package.json` conforme ADR-0015 (React 18.3.1, TypeScript 5.4.5, Ionic 8.2.6, Capacitor 6.1.2, `@capacitor-community/sqlite` 6.0.2, Vite 5.2.11). Evidência: `spike-inf007/package.json`.
- [ ] pendência: `npm install` limpo validado empiricamente. Owner: quem executar E15-S02 (primeiro passo). Comando: `cd spike-inf007 && npm install 2>&1 | tee npm-install.log` e verificar ausência de `npm ERR!`. Se falhar, aplicar fallback conforme plan.md §2 passo 5 e registrar conflito em `Conflitos resolvidos`.
- [x] Verdict SQLCipher preliminar registrado como **(a) sem bloqueador conhecido** — seguir com SQLCipher. Pendência de re-verificação empírica dos candidatos C1–C3 em E15-S02. Evidência: seção "Riscos de plataforma" deste documento.
- [ ] pendência: endpoint de auth testado manualmente via `curl` retornando JWT/Sanctum token válido. Owner: quem executar E15-S02. Depende de débito `FE-API-01`: expor `POST /api/auth/login` JSON (hoje `/auth/login` retorna HTML Livewire). Registrado como débito em `project-state.json[technical_debt]` se ainda não existir.
- [x] Lista de tabelas candidatas a mirror SQLite consolidada em `docs/frontend/api-endpoints.md` seção "Schema local" (11 tabelas com `tenant_id` declarado conforme ADR-0016). Evidência: `docs/frontend/api-endpoints.md`.
- [x] Descarte do frontend antigo confirmado (20 Blades + 2 JS + 1 CSS). Nenhum arquivo legado será reaproveitado. Evidência: seção "Descarte" deste documento.
- [x] Mapeamento completo dos endpoints E01/E02/E03 documentado em `docs/frontend/api-endpoints.md`, incluindo auth, tenants e healthcheck sem lacunas. Evidência: `docs/frontend/api-endpoints.md`.

**Resumo:** 5 de 7 itens verificados no spike; 2 pendências explícitas com owner declarado. Nenhuma pendência é bloqueadora para o **gate** do spike — todas são obrigação de E15-S02 antes do scaffold.

---

## Decisões de design do spike

Decisões tomadas e registradas conforme plan.md §3. Nenhuma cria ADR nova — todas informam possível emenda futura.

### D-1 — Manter ou recomendar emenda à ADR-0015 sobre SQLCipher

- **Decisão:** manter ADR-0015 como está. Verdict preliminar é **(a) sem bloqueador conhecido** para SQLCipher.
- **Plano B candidato** (se E15-S02 detectar bloqueador confirmado): SQLite puro + criptografia por-campo via libsodium (`@noble/ciphers` ou `tweetnacl`), chave derivada de JWT + PBKDF2. Custo: perda de encryption-at-rest transparente e do wipe automático. Validação do plano B em si acontece em E15-S06, **não neste spike**.
- **Reversibilidade:** média — trocar cipher manual por SQLCipher quando upstream resolver é ~1 slice futuro.

### D-2 — Política de `--legacy-peer-deps`

- **Decisão:** não aplicável neste spike (npm install adiado). Registrado como item de pendência do checklist.
- **Rationale para E15-S02:** se `npm install` exigir fallback, documentar qual peer dep específico está em conflito, se é warning semântico ou conflito de runtime, e por que se escolheu `--legacy-peer-deps` em vez de pinagem via `overrides`. `--force` proibido.
- **Reversibilidade:** fácil — ajustável em E15-S02 quando upstream lançar versão corrigida.

### D-3 — Formato do inventário de endpoints

- **Decisão:** adotar **tabela Markdown** (não OpenAPI YAML nesta fase) em `docs/frontend/api-endpoints.md`.
- **Rationale:** o cliente é o único consumidor interno; gerar OpenAPI formal é investimento desproporcional antes de E15-S02. Quando o backlog E17+ adotar Scramble (gerador OpenAPI do Laravel), este documento pode ser convertido.
- **Reversibilidade:** fácil.

---

**Rastreabilidade:**
- Spec: `specs/015/spec.md`
- Plan: `specs/015/plan.md`
- ADRs: ADR-0015 (stack offline-first), ADR-0016 (multi-tenancy)
- Documento irmão: `docs/frontend/api-endpoints.md` (endpoints + schema local)
- PoC descartável: `spike-inf007/`

---
slice: "015"
story: E15-S01
epic: E15
title: "Spike INF-007: Auditoria de reaproveitamento E01/E02/E03 e validação de stack"
lane: L3
status: draft
type: spike
dependencies: []
reqs: ["REQ-SEC-001", "REQ-FLD-001", "REQ-SYN-001"]
adrs: ["ADR-0015", "ADR-0016"]
---

# Slice 015 — Spike INF-007: Auditoria de reaproveitamento E01/E02/E03 e validação de stack

## Contexto

Este slice é um **spike de investigação**, não de implementação. É pré-requisito de todas as demais stories de E15 (PWA Shell Offline-First + Capacitor). Seu propósito é fechar duas incertezas antes que o scaffold do novo frontend comece em E15-S02:

1. **Reaproveitamento do backend E01/E02/E03** — o backend Laravel foi implementado e está merged. O frontend Livewire/Blade antigo será descartado (ADR-0015). Precisamos mapear exatamente o que do backend é consumível pelo novo cliente PWA+Capacitor: endpoints REST, formato de auth JWT, tabelas que precisarão de espelho local em SQLite.
2. **Validação da stack ADR-0015** — as versões específicas de React, TypeScript, Ionic, Capacitor, `@capacitor-community/sqlite` e SQLCipher precisam ser validadas em conjunto (peer deps + issues conhecidos em iOS 17+ e Android 14+). Sem isso, E15-S02 arrisca retrabalho.

**Este slice não gera código de produção.** A saída são dois documentos de referência (`docs/frontend/api-endpoints.md` e `docs/frontend/stack-versions.md`) e, opcionalmente, um projeto temporário de PoC em `/spike-inf007/` que comprove `npm install` limpo com as versões escolhidas.

## Jornada do usuário

> Persona: próximo executor de E15-S02 (scaffold Capacitor). Este spike é spike de investigação; a "jornada" é a experiência de quem consumirá os artefatos do spike.


1. Quem iniciar E15-S02 abre `docs/frontend/stack-versions.md` → encontra versões exatas validadas + checklist de pré-condições marcado.
2. Abre `docs/frontend/api-endpoints.md` → encontra lista completa de endpoints do backend para consumir.
3. Confere seção "Riscos de plataforma" → sabe se SQLCipher segue como planejado ou se precisa plano B.
4. Confere seção "Schema local" → sabe quais tabelas precisarão de espelho local para E15-S06.
5. Inicia scaffold Capacitor sem surpresas de compatibilidade.

## Critérios de aceite

### AC-001 — Documento de endpoints mapeados existe e está completo
**Dado** que o spike foi concluído
**Quando** o revisor abre `docs/frontend/api-endpoints.md`
**Então** o arquivo lista todos os endpoints dos épicos E01/E02/E03 com: URL, método HTTP, headers de autenticação, formato de payload e resposta de exemplo — sem lacunas para endpoints de auth, tenants e healthcheck

### AC-002 — Versões de pacotes validadas e registradas
**Dado** que o spike foi concluído
**Quando** o revisor abre `docs/frontend/stack-versions.md`
**Então** o arquivo declara as versões exatas de React, TypeScript, Ionic, Capacitor, `@capacitor-community/sqlite`, SQLCipher, Vite e confirma que a combinação não tem conflitos de peer dependencies — evidenciado por `npm install` sem erros em um projeto temporário de prova de conceito

### AC-003 — Issues de SQLCipher em iOS 17+ / Android 14+ documentados
**Dado** que o spike foi concluído
**Quando** o revisor abre `docs/frontend/stack-versions.md` seção "Riscos de plataforma"
**Então** o documento lista pelo menos os issues relevantes abertos no repositório `@capacitor-community/sqlite` com data e status, e declara explicitamente: (a) sem bloqueador — seguir com SQLCipher, ou (b) bloqueador identificado — plano B é X

### AC-004 — Tabelas para espelho local mapeadas
**Dado** que o spike foi concluído
**Quando** o revisor abre `docs/frontend/api-endpoints.md` seção "Schema local"
**Então** o documento lista as tabelas do backend que precisarão de espelho local em SQLite (E15-S06), com coluna `tenant_id` identificada em cada uma, conforme ADR-0016

### AC-005 — Frontend antigo descartado formalmente
**Dado** que o spike foi concluído
**Quando** se executa `find resources/views -name "*.blade.php" | wc -l` e `find resources/js -name "*.js" -o -name "*.ts" | grep -v node_modules | wc -l` no repositório
**Então** os arquivos Livewire/Blade e JS legado do frontend antigo estão listados em `docs/frontend/stack-versions.md` seção "Descarte" — e o spike confirma que nenhum deles será reaproveitado no novo frontend

### AC-006 — Checklist de pré-condições para E15-S02 produzida
**Dado** que o spike foi concluído
**Quando** o revisor abre `docs/frontend/stack-versions.md` seção "Pré-condições E15-S02"
**Então** o documento contém checklist com todos os itens marcados como verificados ou com pendência explícita, cobrindo: versões de pacotes, endpoint de auth funcional, plano para SQLCipher, plano de descarte do frontend antigo

## Fora de escopo

- Implementação de qualquer código de produção no cliente (scaffold Capacitor é E15-S02)
- Decisão de sync engine (PowerSync vs ElectricSQL vs custom) — fica para ADR-0017 no início de E16
- Configuração de contas Apple Developer / Google Play Console (ação PM, posterior)
- Ajuste de endpoints do backend — se detectadas lacunas, registrar em débito técnico; não corrigir aqui

## Evidências necessárias para aprovação

- `docs/frontend/api-endpoints.md` criado e preenchido (AC-001, AC-004)
- `docs/frontend/stack-versions.md` criado e preenchido (AC-002, AC-003, AC-005, AC-006)
- `spike-inf007/package.json` existe com as versões exatas e `spike-inf007/npm-install.log` prova `npm install` sem erros — OU equivalente documentado inline em `stack-versions.md` com output do comando
- Nenhum arquivo em `app/`, `resources/`, `routes/`, `database/` alterado (apenas leitura)

## Riscos

- **R1 — Issues de SQLCipher em iOS 17+/Android 14+ podem ser bloqueadores.** Mitigação: documentar plano B (libsodium cipher manual) antes de iniciar E15-S06. Se bloqueador confirmado, escalar para PM antes de E15-S02.
- **R2 — Backend pode ter endpoints não documentados em docs/.** Mitigação: usar `routes/api.php` + `routes/web.php` + `grep -r "Route::"` como fonte primária da verdade, não depender de documentação prévia.
- **R3 — Versões declaradas na ADR-0015 podem estar em conflito de peer deps.** Mitigação: se conflito for detectado, registrar resolução e, se necessário, abrir emenda à ADR-0015 ao invés de mascarar com `--force`.

## Estimativa

8–12 horas de esforço (1 pessoa): pesquisa de issues 3h, mapeamento de endpoints 3h, PoC de compatibilidade de versões 3h, redação dos documentos 2h.

## Rastreabilidade

- Story: `epics/E15/stories/E15-S01.md`
- ADRs: ADR-0015 (stack offline-first), ADR-0016 (multi-tenant isolation)
- REQs: REQ-SEC-001, REQ-FLD-001, REQ-SYN-001
- Desbloqueia: E15-S02 (scaffold Capacitor), E15-S06 (schema SQLite local)

# Incidente — Gap de descoberta: offline-first sistêmico

**Data:** 2026-04-16
**Severidade:** S1 (crítico — invalida fundamentos de produto e stack)
**Reportado por:** PM (roldao-tecnico)
**Status:** aberto — re-discovery em andamento

## 1. O que aconteceu

Durante conversa exploratória sobre fluxo do técnico em campo, o PM reagiu à afirmação do agente de que "offline-first está fora do MVP" (conforme `docs/product/mvp-scope.md:78` e `docs/product/ui-flows.md:86`).

Declaração literal do PM:
> "NOSSO TRABALHO É 90% OFFLINE. [...] O SISTEMA TEM QUE SER OPERACIONAL OFFLINE E ONLINE AUTOMATICAMENTE, O VENDEDOR POR EXEMPLO, ELE TEM QUE TER O ACESSO AO CRM OFFLINE E ETC."

Isso significa que **offline-first é requisito sistêmico**, não feature de um épico isolado. Todos os papéis (vendedor, técnico, atendente, gerente) precisam operar com conexão intermitente, sincronização automática e sem perda de dados.

## 2. Impacto

Atinge fundamentos já congelados:

- **PRD** — baseado em personas e jornadas que assumem conectividade. Frozen em 2026-04-11.
- **MVP-scope** — linha 78 exclui explicitamente app mobile nativo; linha 86 de `ui-flows.md` exclui fila local, edição offline e sync automática.
- **Personas** — persona primária é gerente de bancada; técnico de campo não é persona primária; vendedor em campo não aparece.
- **Jornadas** — `docs/product/journeys.md` desenhadas para operação conectada no laboratório.
- **ADR-0001 (stack)** — Laravel + PostgreSQL + SPA escolhida sem considerar offline-first. Stack natural para offline-first é diferente (Service Worker + IndexedDB/SQLite + fila de sync + resolução de conflitos).
- **Roadmap (14 épicos)** — decomposição assume web online.
- **E01, E02, E03** — já implementados (merged). Backend Laravel e auth possivelmente salváveis; frontend provavelmente precisa ser refeito em PWA ou app híbrido.

## 3. Causa raiz

Intake original (`/intake` pré-2026-04-11) não capturou a natureza field-heavy e offline-intensive do negócio real. O agente conduziu a entrevista assumindo laboratório de bancada, e o PM não corrigiu na hora por não saber que aquela assunção excluía offline.

**Falha de processo:** as 10 perguntas estratégicas do `/intake` não incluem "qual é o perfil de conectividade da operação?" como pergunta obrigatória. Isso é uma lacuna do harness a corrigir via R16 (harness-learner) após a retrospectiva.

## 4. Decisão do PM (2026-04-16)

1. **Parar E03 imediatamente.** Nenhum slice novo de CRUD cliente/contato enquanto a descoberta não for refeita.
2. **PRD e MVP-scope marcados como `SUPERSEDED — pending re-discovery`.**
3. **Re-intake** focado em offline-first sistêmico, com todos os papéis.
4. **Personas, jornadas, MVP-scope, ADR de stack, roadmap** — todos serão revistos após o re-intake.
5. **E01 (setup), E02 (auth), E03 (cliente CRUD já merged)** — auditar o que é aproveitável depois que a nova stack for definida.

## 5. Próximos passos

- [ ] Re-intake conversacional com o PM (desta sessão em diante)
- [ ] Nova ADR de stack offline-first (ADR-0015 ou revisão de ADR-0001)
- [ ] Personas 2.0 (`docs/product/personas.md` — superseded backup + versão nova)
- [ ] Jornadas 2.0 (`docs/product/journeys.md` — idem)
- [ ] MVP-scope 2.0
- [ ] Roadmap 2.0
- [ ] Auditoria técnica de E01/E02/E03 para identificar aproveitamento
- [ ] Retrospectiva R15 do incidente + aprendizado R16 para corrigir o `/intake`

## 6. Aprendizado para o harness (R16)

A skill `/intake` deve incluir perguntas obrigatórias de **contexto operacional**, não só de domínio:

- Qual o perfil de conectividade da operação? (always-on / intermitente / frequentemente offline)
- Qual a distribuição de papéis por localização? (escritório / campo / híbrido)
- Existem papéis que precisam operar sem internet de forma funcional?
- Qual é o dispositivo primário de cada papel? (desktop / notebook / tablet / smartphone)

Sem essas perguntas, a escolha de stack e escopo de MVP pode ser gravemente equivocada.

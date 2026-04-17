# E15 — PWA Shell Offline-First + Capacitor Wrapper: Índice de Stories

**Épico:** E15
**Status geral:** backlog
**Complexidade relativa:** muito alta (foundational, stack nova, multi-plataforma, segurança crítica)
**Estimativa total:** 10 stories

---

## Stories

| ID | Título | Complexidade | ACs | Depende de | Status |
|---|---|---|---|---|---|
| E15-S01 | Spike INF-007: Auditoria de reaproveitamento E01/E02/E03 e validação de stack | média | 6 | — | backlog |
| E15-S02 | Scaffold React + TypeScript + Ionic + Capacitor + Vite | alta | 8 | E15-S01 | backlog |
| E15-S03 | PWA: Service Worker + manifest + instalabilidade offline | média | 7 | E15-S02 | backlog |
| E15-S04 | Wrapper Capacitor iOS (projeto Xcode + build ad-hoc TestFlight) | alta | 6 | E15-S02 | backlog |
| E15-S05 | Wrapper Capacitor Android (projeto Gradle + build internal track) | alta | 6 | E15-S02 | backlog |
| E15-S06 | Banco local SQLite + SQLCipher + helper de query com enforcement `tenant_id` | alta | 9 | E15-S02 | backlog |
| E15-S07 | Autenticação: JWT long-lived + refresh silencioso + tela de login | alta | 8 | E15-S02, E15-S06 | backlog |
| E15-S08 | Device binding: registro de dispositivo + endpoint `/api/v1/devices` | alta | 7 | E15-S07 | backlog |
| E15-S09 | Biometria (Face ID / fingerprint) + WebAuthn fallback desktop | alta | 8 | E15-S07, E15-S08 | backlog |
| E15-S10 | Wipe remoto: flag + endpoint admin + UI + lógica de limpeza no cliente | alta | 10 | E15-S08 | backlog |

---

## Notas de sequenciamento (R13)

- **E15-S01** é pré-requisito de todas as demais (spike de auditoria técnica — Spike INF-007). Sem o spike, decisões de versão de pacotes são suposição.
- **E15-S02** é pré-requisito de S03, S04, S05 e S06. Após S02 concluída, S03+S04+S05 podem rodar em **paralelo** entre si (plataformas independentes). S06 também pode rodar em paralelo com S03/S04/S05 após S02.
- **E15-S07** depende de S02 (scaffold) e S06 (banco local), mas S03/S04/S05 podem estar em progresso simultaneamente.
- **E15-S08** depende de S07 (precisa de JWT estabelecido).
- **E15-S09** e **E15-S10** dependem de S08 (device binding estabelecido). Podem rodar em paralelo entre si.
- Observabilidade (Sentry, logs locais, ping de health) é incorporada em S07 e S10, sem story dedicada — integrada nas evidências de cada story.

---

## Dependências externas (bloqueiam deploy, não desenvolvimento)

- Conta Apple Developer (PM) — necessária para S04 (build TestFlight)
- Conta Google Play Console (PM) — necessária para S05 (build internal track)
- Spike INF-007 (E15-S01) concluído — necessário antes de S02

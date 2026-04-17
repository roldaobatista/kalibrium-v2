# E21 — Compliance Fiscal, LGPD, Backup e Push: Índice de Stories

**Épico:** E21
**Status geral:** backlog
**Complexidade relativa:** alta (fiscal + LGPD + infra)
**Estimativa total:** 6 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E21-S01 | Retransmissão de NFS-e rejeitada (estado + tela diagnóstico + fluxo correção) | alta | 8 | — | backlog |
| E21-S02 | Retenções fiscais por regime (ISS + IR + INSS + half-even rounding) | alta | 9 | — | backlog |
| E21-S03 | Push notification nativo (FCM + Capacitor + preferências do usuário) | alta | 7 | — | backlog |
| E21-S04 | Jornada LGPD do titular (formulário + triagem DPO + log imutável + anonimização) | alta | 10 | — | backlog |
| E21-S05 | Backup por tenant (job diário + S3 + hash SHA-256 + painel de status) | média | 7 | — | backlog |
| E21-S06 | Verificação de integridade do backup (job semanal + restauração automatizada) | média | 5 | E21-S05 | backlog |

---

## Notas de sequenciamento (R13)

- **E21-S01**, **E21-S02**, **E21-S03**, **E21-S04** e **E21-S05** não dependem entre si — podem rodar em **paralelo** (domínios completamente independentes: fiscal, push, LGPD, backup).
- **E21-S06** depende de S05 (verifica backups gerados por S05).
- Recomendação de prioridade interna: S01 e S02 primeiro (desbloqueiam clientes em produção com rejeição fiscal), S04 segundo (compliance legal), S03 e S05 terceiro.

---

## Dependências externas

- E07 merged (NFS-e base para S01)
- E12 merged (infraestrutura de comunicação para S03)
- E15 merged (Capacitor shell para push em S03)
- ADR-0016 aceita (backup tenant-scoped, logs LGPD)

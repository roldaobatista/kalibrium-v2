# E24 — Operação Robusta e Qualidade ISO 17025 Ampliada: Índice de Stories

**Épico:** E24
**Status geral:** backlog
**Complexidade relativa:** muito alta (ISO 17025 + despacho + suspensão retroativa)
**Estimativa total:** 7 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E24-S01 | Competências técnicas vigentes por técnico (CRUD + upload + bloqueio em OS) | alta | 9 | — | backlog |
| E24-S02 | Dual sign-off no certificado (fluxo executor → verificador → emissão) | alta | 9 | — | backlog |
| E24-S03 | Suspensão retroativa de certificados por falha de padrão (lote + notificação) | alta | 8 | — | backlog |
| E24-S04 | Agendamento automático de recalibração de padrão (scheduler 90d + bloqueio) | média | 6 | — | backlog |
| E24-S05 | Despacho round-robin + re-despacho automático + notificação ao cliente | alta | 8 | — | backlog |
| E24-S06 | OS de garantia (tipo + custo zero + classificação + relatório de qualidade) | média | 6 | — | backlog |
| E24-S07 | Agendamento automático de manutenção preventiva de UMC e veículos (scheduler KM/tempo) | média | 6 | — | backlog |

---

## Notas de sequenciamento (R13)

- **E24-S01** a **E24-S07** são domínios independentes entre si e podem rodar em **paralelo** (`dependencies: []` para todas, exceto onde indicado abaixo).
- **E24-S04** pode ser implementada após S01 e S03 estarem merged (recalibração usa bloqueio de competência indiretamente), mas não é bloqueante técnico imediato.
- Recomendação de prioridade interna: S01 + S02 (compliance ISO obrigatório) → S03 (risco reputacional) → S04 + S07 (schedulers) → S05 + S06.

---

## Dependências externas

- E04 merged (OS base — bloqueio de competência e dual sign-off são extensões do fluxo)
- E22 merged (SPC — suspensão retroativa complementa monitoramento de drift)
- E17 merged (UMC — scheduler de manutenção de frota usa entidades de E17)
- E05 merged (certificados — suspensão retroativa afeta certificados emitidos)
- E12 merged (e-mail — notificações de suspensão e re-despacho)
- ADR-0016 aceita (competências, sign-offs, suspensões — tudo tenant-scoped)

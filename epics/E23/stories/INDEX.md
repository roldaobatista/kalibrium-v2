# E23 — Revalidação Proativa e Engajamento de Cliente Recorrente: Índice de Stories

**Épico:** E23
**Status geral:** backlog
**Complexidade relativa:** alta
**Estimativa total:** 4 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E23-S01 | Detecção proativa de vencimento (job diário + fases D-90/60/30/7 + config por tenant) | alta | 8 | — | backlog |
| E23-S02 | Cadência de comunicação (e-mail + WhatsApp + log de entrega + opt-out LGPD) | alta | 9 | E23-S01 | backlog |
| E23-S03 | Link de agendamento público (token + seleção de data + conversão em OS) | alta | 8 | E23-S02 | backlog |
| E23-S04 | Painel de pipeline de revalidações (fases + status + taxa de conversão + exportação) | média | 6 | E23-S01 | backlog |

---

## Notas de sequenciamento (R13)

- **E23-S01** é fundação (detecta instrumentos e cria campanhas). Pré-requisito de S02 e S04.
- **E23-S02** depende de S01 (precisa da campanha criada para disparar comunicação).
- **E23-S03** depende de S02 (link é gerado junto com a comunicação).
- **E23-S04** depende de S01 e pode rodar em **paralelo** com S02 e S03 (painel usa dados da campanha, não da comunicação).

---

## Dependências externas

- E20 merged (CRM base — contatos de cliente)
- E12 merged (infraestrutura de e-mail)
- E05 merged (certificados com data de validade)
- ADR-0016 aceita (campanhas tenant-scoped)

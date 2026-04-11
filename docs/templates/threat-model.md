# Template — Threat model (STRIDE)

> **Uso:** copiar para `docs/security/threat-model-<escopo>.md` (ou para `specs/NNN-<slug>/threat-model.md` quando for específico de um slice). Item 6.9 dos micro-ajustes da meta-auditoria #2. O threat model inicial do Kalibrium está em `docs/security/threat-model.md`.

## 1. Identificação

- **Título:** escopo do threat model (exemplo: "Threat model — portal do cliente final")
- **Autor:** nome do PM ou do DPO
- **Data desta versão:** AAAA-MM-DD
- **Versão:** v1, v2, ...
- **Status:** rascunho / em revisão / aprovado / revisão externa necessária
- **Documento-base:** arquivo(s) sobre o qual o STRIDE é aplicado (exemplo: `docs/architecture/foundation-constraints.md`)

## 2. Escopo

Descrever em 2-3 parágrafos: qual é o subsistema / slice / processo sendo modelado, quais são as fronteiras (o que está dentro, o que está fora), qual é o horizonte temporal (MVP / fase 2).

## 3. Superfícies de ataque

Lista enumerada das superfícies. Cada item com 1 linha descrevendo o que é.

1. ...
2. ...

## 4. Ativos a proteger

O que acontece se for comprometido. Lista numerada com a natureza do ativo (dado pessoal? credencial? integridade regulatória? disponibilidade?).

1. ...
2. ...

## 5. Ameaças identificadas (STRIDE)

Tabela com um ID por ameaça (`T-NNN`), seguindo a mesma estrutura do `docs/security/threat-model.md` raiz. Mínimo 12 ameaças cobrindo as 6 categorias STRIDE.

| ID | Categoria | Descrição da ameaça | Superfície | Impacto | Mitigação proposta |
|---|---|---|---|---|---|
| T-001 | S (Spoofing) | ... | ... | ... | ... |
| T-002 | T (Tampering) | ... | ... | ... | ... |
| T-003 | R (Repudiation) | ... | ... | ... | ... |
| T-004 | I (Info disclosure) | ... | ... | ... | ... |
| T-005 | D (DoS) | ... | ... | ... | ... |
| T-006 | E (Elevation) | ... | ... | ... | ... |
| ... | ... | ... | ... | ... | ... |

Cada ameaça em exatamente uma categoria. Se for ambígua, preferir a categoria que representa melhor o impacto final no titular ou no ativo.

## 6. Mitigações aceitas vs rejeitadas

Quando a mitigação proposta na tabela §5 foi revisada pelo DPO ou pelo PM, marcar aqui quais foram aceitas, quais foram rejeitadas (com justificativa) e quais ficaram "aceitas com ressalva".

## 7. Riscos residuais

Depois das mitigações aceitas, quais riscos permanecem abertos? Lista numerada com severidade residual (alta/média/baixa) e decisão (aceitar / mitigar depois / bloquear lançamento).

## 8. Pendências de revisão externa

Quem precisa revisar e assinar antes de este threat model virar `aprovado`? Listar os papéis (DPO, consultor de segurança, advisor técnico) e a data-alvo.

## 9. Cross-ref obrigatório

- `docs/security/threat-model.md` (raiz)
- `docs/security/lgpd-base-legal.md`
- `docs/security/dpia.md`
- `docs/architecture/foundation-constraints.md`
- `docs/security/incident-response-playbook.md`

## 10. Assinatura

- **Autor:** nome + data
- **Revisor DPO** (obrigatório para escopos que tocam dado pessoal): nome + data + veredito
- **Revisor PM:** nome + data

---

**Regra final:** nenhuma seção pode ficar em branco. Se não se aplica, escrever "não aplicável" + razão de 1 linha. Threat model sem assinatura do DPO (quando aplicável) é **rascunho**, nunca `aprovado`.

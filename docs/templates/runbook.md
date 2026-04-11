# Template — Runbook operacional

> **Uso:** copiar para `docs/ops/runbooks/<slug>.md`. Um runbook por procedimento operacional recorrente (exemplos: "como restaurar backup", "como trocar de provedor de NFS-e", "como rotacionar segredo"). Item 6.9 dos micro-ajustes da meta-auditoria #2.

## 1. Identificação

- **Título:** o nome do procedimento, curto. Exemplo: "Restauração de backup do tenant".
- **Responsável primário:** quem executa normalmente (PM, agente + PM, DPO).
- **Responsável de escalação:** quem assume se o primário não pode.
- **Data da última revisão:** AAAA-MM-DD
- **Próxima revisão:** AAAA-MM-DD

## 2. Quando usar este runbook

Lista de situações que disparam a execução. Exemplo: "Backup corrompido ao ser lido", "Tenant pediu restauração de 48h atrás", "Migração de versão falhou no meio".

## 3. Pré-condições

O que precisa estar verdade **antes** de começar. Exemplo: acesso ao painel do fornecedor, credencial de leitura do bucket, incident file aberto, autorização do PM.

## 4. Riscos conhecidos

O que pode dar errado durante a execução e qual o impacto. Usado para decidir se vale rodar em horário de pico ou em janela protegida.

## 5. Passos (enumerados)

Cada passo é uma ação atômica. Não misturar duas ações no mesmo passo.

1. [Ação] — espera-se [saída].
2. [Ação] — espera-se [saída].
3. ...
4. **Ponto de decisão:** se [condição], ir para passo X; senão, ir para passo Y.
5. ...

Sempre que possível, incluir o comando literal dentro de bloco de código. Preferir comando idempotente.

## 6. Validação pós-execução

Como confirmar que o procedimento funcionou. Lista de checks com comando + saída esperada.

- Check 1: ...
- Check 2: ...

## 7. O que fazer se falhar no meio

Se qualquer passo falhou e o procedimento não terminou limpo:

1. **Não continuar**. Capturar evidência (screenshot, log, dump).
2. Registrar em `docs/incidents/<slug>-YYYY-MM-DD.md`.
3. Reverter o que for possível reverter.
4. Escalar conforme §1 — responsável de escalação.

## 8. Histórico de execuções

Tabela viva. Uma linha por execução real.

| Data | Executante | Motivo | Resultado | Tempo gasto | Notas |
|---|---|---|---|---|---|
| AAAA-MM-DD | nome | ... | ok / parcial / falhou | Xh Ymin | ... |

## 9. Pontos de melhoria conhecidos

Lista de coisas que tornariam o runbook mais fácil na próxima execução. Cada item vira candidato a slice futuro.

## 10. Cross-ref

- `docs/ops/oncall.md`
- `docs/security/incident-response-playbook.md`
- Outros runbooks relacionados
- Procedimento §9 do `CLAUDE.md` (se o procedimento envolve arquivos selados)

---

**Regra final:** runbook não-testado é ficção. Cada runbook deve ter pelo menos uma execução em ambiente de staging registrada em §8 antes de ser usado em produção real.

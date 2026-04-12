---
description: Traduz um slice técnico em relatório de linguagem de produto para o humano (PM, não desenvolvedor). R12 enforcement. Uso obrigatório em escalações R6 e opcional após merge. Uso: /explain-slice NNN.
---

# /explain-slice

## Propósito

O humano no Kalibrium V2 é **Product Manager, não desenvolvedor** (ver `docs/incidents/pr-1-admin-merge.md` §Causa raiz). Toda saída técnica que ele precise ver deve ser **traduzida** para linguagem de produto.

Esta skill lê artefatos técnicos do slice (`spec.md`, `plan.md`, `verification.json`, `review.json`, telemetria) e gera um relatório **apenas em termos de produto**: o que foi feito, o que ficou funcionando, o que está pronto pra usar, o que precisa da decisão humana.

## Uso
```
/explain-slice NNN
```

## Quando usar

1. **Obrigatório** em escalações R6 (verifier ou reviewer reprovou 2x) — humano precisa decidir sem entender o diff.
2. **Opcional** após merge bem-sucedido — relatório informativo do que entrou em produção.
3. **Opcional** antes de decidir stack (ADR-0001) — usa-se para descrever trade-offs em linguagem de produto.

## Vocabulário permitido (R12)

✅ **OK:**
- funcionalidade, tela, botão, formulário, campo
- cliente, usuário, cadastro, login, senha
- certificado, relatório, PDF, planilha, exportação
- lista, filtro, ordenação, busca
- notificação, e-mail, WhatsApp, alerta
- cálculo, valor, total, percentual, desconto
- "funciona", "está pronto", "faltou", "deu erro"
- analogias do dia-a-dia: "é como um caderno digital", "parecido com planilha do Excel"

❌ **Proibido:**
- class, function, method, endpoint
- schema, migration, seed, fixture
- refactor, dependency, import, module
- async, callback, promise, await
- PR, commit, branch, merge, rebase
- types, interface, generic
- SQL, query, JOIN, transaction

## O que faz

1. Lê `specs/NNN/spec.md` (descrição original em NL)
2. Lê `specs/NNN/verification.json` e `specs/NNN/review.json` se existirem
3. Lê `.claude/telemetry/slice-NNN.jsonl` para números
4. Gera `docs/explanations/slice-NNN.md` com seções fixas:

```markdown
# Slice NNN — <título em português de produto>

## O que foi feito (em 3 linhas)
<descrição do comportamento entregue, do ponto de vista do usuário>

## Status
✓ pronto para usar / ⚠ precisa da sua decisão / ✗ não concluído

## O que o usuário final vai ver
<lista de funcionalidades visíveis: telas, botões, campos, notificações>

## O que funcionou
<bullets em PT-BR de produto>

## O que NÃO está neste slice (fica pra depois)
<escopo excluído, em termos de produto>

## Se algo precisar da sua decisão
<só preencher se houver escalação — pergunta simples sim/não ou A/B>

## Próximo passo
<ação única e clara: "testar na tela de clientes", "decidir se cobra o envio de WhatsApp", etc.>
```

5. Se houver escalação R6: também envia notificação (future: WhatsApp/email quando configurado)

## Implementação

```bash
bash scripts/explain-slice.sh "$1"
```

## Exemplo de tradução

**Entrada técnica (verification.json findings):**
```json
{"rule": "P2", "file": "src/cert/pdf.ts", "line": 42,
 "reason": "função generatePDF sem teste mapeado para AC-003"}
```

**Saída em produto:**
> "A parte do certificado em PDF ainda não foi testada automaticamente no passo 3 da especificação (gerar o arquivo). Precisamos garantir que o arquivo sai certo antes de liberar para os clientes."

## Regras do tradutor

1. **Nunca** citar nomes de arquivo, linha, função, variável no texto principal. Se for essencial, colocar em "detalhes técnicos" colapsável no final.
2. **Sempre** explicar impacto no usuário final ou no cliente do tenant.
3. **Nunca** deixar o humano sem próximo passo claro.
4. Se a tradução for ambígua ou impossível, escrever "precisa de revisão técnica" e marcar o slice pra não mergear até um humano técnico estar disponível (no futuro).

## Pré-condições

1. `specs/NNN/spec.md` existe (slice já foi criado).
2. Pelo menos um artefato técnico do slice existe (`verification.json`, `review.json`, ou telemetria em `.claude/telemetry/slice-NNN.jsonl`).
3. Em caso de escalação R6: os dois `rejected` consecutivos devem estar registrados em `verification.json` ou `review.json`.

## Agentes

Nenhum — executada pelo orquestrador.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Slice `NNN` não existe (`specs/NNN/` ausente) | Informar PM que o slice não foi encontrado. Verificar numeração correta com `/where-am-i` ou `/status`. |
| Artefatos técnicos incompletos (sem `verification.json`, sem testes) | Gerar explicação parcial com o que estiver disponível. Indicar claramente quais informações estão faltando no relatório. |
| Tradução impossível (termo técnico sem equivalente de produto) | Usar a regra 4 do tradutor: marcar "precisa de revisão técnica" e colocar o termo em seção colapsável de detalhes técnicos. |
| PM não entende a explicação gerada | Simplificar ainda mais, usando analogias do dia-a-dia. Perguntar ao PM qual parte ficou confusa e reformular. |

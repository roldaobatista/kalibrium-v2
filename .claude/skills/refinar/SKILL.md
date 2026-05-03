---
name: refinar
description: Continua o refinamento de uma ideia já capturada em docs/backlog/ideias/. Faz mais 2-3 perguntas curtas em pt-BR pra fechar regras de negócio antes de virar história. Use quando Roldão diz "vamos voltar naquela ideia X" ou "lembra daquela ideia, tem mais detalhe".
disable-model-invocation: true
---

# /refinar

Quando uma ideia foi capturada com `/nova-ideia` mas ainda não virou história, esta skill retoma o refinamento — faz mais perguntas pra fechar a regra de negócio.

## Passos

### 1. Listar ideias pendentes de refinamento

Listar arquivos em `docs/backlog/ideias/*.md` que tenham status `[ ] refinada` (ou `[ ] virou história`).

Mostrar ao Roldão em pt-BR:

```
Ideias capturadas que ainda não viraram história:
  1. <título 1> (capturada em <data>)
  2. <título 2> (capturada em <data>)

Qual você quer refinar agora?
```

Se foi passado argumento (slug ou número), pular esta etapa e ir direto na ideia escolhida.

### 2. Ler o que já foi falado

Ler o arquivo da ideia inteiro. Resumir em pt-BR pro Roldão:

-   O que ele falou originalmente
-   O que já foi perguntado e respondido
-   Qual o ponto cego que ainda falta fechar

### 3. Fazer 2-3 perguntas novas

Foco em **regra de negócio**, nunca técnica. Exemplos:

-   "E se o equipamento estiver inativo, ainda assim aparece no aviso?"
-   "Vale só pro plano pago ou todos os clientes?"
-   "Quem pode mudar essa configuração — só o gestor ou qualquer usuário?"

Uma de cada vez quando possível. Se múltiplas, numerar.

### 4. Anotar respostas no arquivo da ideia

Adicionar em "Contexto adicional" cada pergunta + resposta nova.

### 5. Decidir o próximo passo

Se a ideia agora está fechada o suficiente:

> "Acho que agora dá pra virar história formal. Posso transformar?"

Se ainda falta algo:

> "Tem mais X que precisa fechar antes — quando você tiver decidido, me chama de novo com `/refinar`."

Se vier ok pra virar história, chamar fluxo da `/nova-ideia` (passo "Oferecer transformar em história") usando o conteúdo já refinado.

## Princípios

-   **Sem jargão técnico.** Falar "cliente", "tela", "lista", "aviso".
-   **Regra de negócio, não técnica.** Não perguntar sobre banco, model, API.
-   **Pequenas doses.** Máximo 3 perguntas por chamada — se precisar mais, fechar a chamada e marcar pra continuar depois.
-   **Sempre atualizar o arquivo da ideia.** Toda resposta vai pra dentro do arquivo, nada perdido.

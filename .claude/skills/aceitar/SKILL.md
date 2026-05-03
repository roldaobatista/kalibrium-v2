---
name: aceitar
description: Roldão aceita formalmente uma história que está em ativas/ depois de olhar o roteiro de aceite com imagens. Marca como aceita e prepara pro arquivamento. Use quando Roldão diz "tá do jeito que eu queria", "é isso, pode arquivar", "aceito a história X".
disable-model-invocation: true
---

# /aceitar

Roldão olhou o roteiro de aceite em `docs/backlog/aceites/<slug>.md` (gerado pelo `e2e-aceite`) e decidiu "é isso". Esta skill formaliza o aceite.

## Passos

### 1. Identificar a história

Se argumento veio (slug), pegar. Senão listar histórias em `docs/backlog/historias/ativas/` e perguntar qual.

### 2. Confirmar que existe roteiro de aceite

Verificar `docs/backlog/aceites/<slug>.md`. Se não existir, dizer:

> "Ainda não tem roteiro de aceite pra essa história. Vou chamar o robô que tira print das telas (`e2e-aceite`) primeiro, e aí você decide olhando as imagens."

E **não aceitar agora** — chamar `e2e-aceite` e parar.

### 3. Ler o roteiro de aceite

Resumir pro Roldão em pt-BR:

-   Quantos caminhos de uso foram cobertos
-   Quantas imagens foram tiradas
-   O que o robô já testou sozinho
-   Se ficou algum caminho sem testar (e por quê)

Pedir confirmação explícita:

> "Você confirma que olhou as imagens e está do jeito que queria? (responde 'aceito' ou diz o que está errado)"

### 4. Se aceito, marcar no arquivo

No arquivo da história em `ativas/`:

-   Marcar `- [x] revisada`
-   Marcar `- [x] pronta`
-   Marcar `- [x] aceita`
-   Adicionar linha `**Aceita em:** AAAA-MM-DD`

No arquivo de aceite em `docs/backlog/aceites/<slug>.md`:

-   Marcar `- [x] Tá do jeito que eu queria — pode subir pro servidor`

### 5. Perguntar sobre subir pro servidor

> "Aceita ✓. Quer que eu rode `/posso-subir` pra ver se está tudo ok pra subir pro servidor que o cliente usa?"

Se sim → chamar skill `/posso-subir`.
Se não → parar aqui, deixar arquivar pra depois (skill `/arquivar` cuida quando for hora).

## Princípios

-   **Aceite é só do Roldão.** Eu nunca aceito por ele. Se ele não disse "tá certo" explicitamente, não marco como aceita.
-   **Sem aceite sem imagens.** Se não tem roteiro visual, chamar `e2e-aceite` antes.
-   **Não arquiva no aceite.** O aceite e o arquivamento são duas etapas — entre elas pode rolar deploy. Skill `/arquivar` separada.

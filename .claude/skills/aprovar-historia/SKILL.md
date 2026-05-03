---
name: aprovar-historia
description: Roldão aprova uma história que está em docs/backlog/historias/aguardando/. Move pra ativas/, atualiza AGORA.md, monta plano técnico em modo Plan e devolve pra revisão. Use quando Roldão diz "aprovo", "vamos fazer essa", "pode começar a história X".
disable-model-invocation: true
---

# /aprovar-historia

Roldão olhou uma história em `aguardando/` e disse "aprovo, pode fazer". Esta skill formaliza isso e dá o pontapé na execução.

## Passos

### 1. Identificar a história

Se argumento veio (slug ou número), pegar direto. Senão listar `docs/backlog/historias/aguardando/*.md` em pt-BR e perguntar qual.

### 2. Confirmar com Roldão em pt-BR

Antes de mover, ler a história e mostrar resumo:

```
Vou aprovar a história "<título>". Resumo do que ela faz:
  - O que o cliente vai ver: <1 linha>
  - Por que importa: <1 linha>
  - Como saber que ficou pronto: <N caminhos numerados>

Confirma?
```

### 3. Mover de aguardando/ pra ativas/

`mv docs/backlog/historias/aguardando/<slug>.md docs/backlog/historias/ativas/<slug>.md`

### 4. Atualizar status no arquivo da história

Trocar `- [ ] planejada` por `- [x] planejada` e marcar `- [x] em andamento`.

### 5. Atualizar AGORA.md

Adicionar a história na seção "Em andamento" com link e data de início.

### 6. Montar plano técnico

Antes de chamar o `executor`, esboçar **plano técnico** em pt-BR técnico (não pra Roldão, pra mim):

-   Quais arquivos vão ser tocados
-   Que migrations precisam (se alguma)
-   Que policies/scopes mexer
-   Que testes Pest cobrir
-   Riscos visíveis

Apresentar plano ao Roldão em pt-BR sem jargão (traduzir cada item):

-   "Vou criar uma tela nova de X, mexer no aviso Y, e adicionar testes."
-   Estimar dificuldade: simples / médio / complicado.

Se Roldão aprovar o plano, chamar subagente `executor`. Se redirecionar, ajustar e mostrar de novo.

### 7. Após `executor` devolver "feito" — disparar revisor + e2e-aceite EM PARALELO

`executor` só implementa, formata, testa e roda PHPStan no escopo. Ele NÃO chama subagentes — quem orquestra é a maestra (eu).

Assim que `executor` devolver, **disparar `revisor` e `e2e-aceite` em paralelo numa única mensagem** (dois blocos `Agent` no mesmo turno):

-   `revisor` — audita 4 lentes (multi-tenant, migration, Livewire, testes) sobre o diff que `executor` produziu.
-   `e2e-aceite` — gera roteiro com imagens em `docs/backlog/aceites/<slug>.md`.

Roda em paralelo porque são independentes: um lê código, o outro navega pela tela. Tempo total ≈ tempo do mais lento (não a soma).

### 8. Consolidar resultados pra Roldão

Quando os dois devolverem:

-   Se `revisor` deu **VERMELHO** → corrigir antes de mostrar aceite. Voltar ao `executor`.
-   Se `revisor` deu **AMARELO** → decidir caso a caso (corrigir agora ou anotar dívida).
-   Se `revisor` deu **VERDE** → mostrar roteiro de aceite ao Roldão em pt-BR sem jargão.

## Princípios

-   **Aprovação é explícita.** Não assumir aprovação se Roldão só "comentou bem". Pedir confirmação clara.
-   **Plano técnico fica fora do arquivo da história.** A história é o "o que cliente vê"; o plano é interno e some depois da execução.
-   **Uma história ativa por vez** (no máximo duas). Se já houver uma ativa, perguntar se Roldão quer pausar ela.

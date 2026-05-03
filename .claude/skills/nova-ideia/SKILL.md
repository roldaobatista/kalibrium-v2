---
name: nova-ideia
description: Captura uma ideia bruta do Roldão (em pt-BR, do jeito que ele falou) e salva em docs/backlog/ideias/. Faz 2-3 perguntas curtas em pt-BR pra refinar. Oferece transformar em história formal quando tiver informação suficiente.
---

# /nova-ideia

Quando o Roldão tem uma ideia nova de funcionalidade, melhoria ou correção, esta skill captura a ideia, salva em arquivo, e refina com perguntas curtas em pt-BR.

## Passo a passo

### 1. Salvar a ideia bruta imediatamente

Antes de qualquer coisa, criar arquivo em `docs/backlog/ideias/AAAA-MM-DD-<slug>.md` com a ideia exatamente como o Roldão falou. Nada se perde, mesmo se a conversa mudar de assunto.

Formato:

```markdown
# <título curto em pt-BR>

**Capturada em:** AAAA-MM-DD
**Falada pelo Roldão assim:**
> <transcrição do que ele disse, sem editar>

## Contexto adicional
(vazio inicialmente — preenche conforme refinamento)

## Status
- [ ] capturada
- [ ] refinada (perguntas respondidas)
- [ ] virou história em `historias/aguardando/<slug>.md`
```

### 2. Fazer 2-3 perguntas em pt-BR pra refinar

Perguntas curtas, uma de cada vez se possível, focadas em **regra de negócio** — não em técnica. Exemplos do tipo de pergunta:

- "Quanto tempo antes você quer ver o aviso? 30 dias? 15?"
- "Vale para todos os tipos de equipamento ou só alguns?"
- "Deve avisar por e-mail também ou só na tela?"
- "Quem pode ver isso? Todos os usuários do laboratório ou só o gestor?"
- "Quando o cliente cancela, o que deve acontecer com os dados?"

Evitar perguntas técnicas como "qual ORM?" / "que tipo de relação no banco?" — isso é decisão da maestra/executor.

### 3. Anotar respostas no arquivo da ideia

Adicionar em "Contexto adicional" cada pergunta + resposta, em pt-BR.

### 4. Oferecer transformar em história

Quando tiver informação suficiente (geralmente após 2-3 perguntas), perguntar ao Roldão:

> "Acho que tenho o suficiente pra transformar isso em uma história formal. Posso seguir?"

Se ele aprovar, mover/copiar pra `historias/aguardando/<slug>.md` no formato:

```markdown
# História: <título em pt-BR>

## O que o cliente vai ver
<descrição visual do que muda na experiência do cliente>

## Por que isso importa
<motivo de produto: dor que resolve, oportunidade, exigência>

## Como saberemos que ficou pronto
1. <caminho de uso 1 em pt-BR>
2. <caminho de uso 2>
...

## Status
- [ ] planejada
- [ ] em andamento
- [ ] revisada
- [ ] pronta
- [ ] aceita
```

E marcar a ideia original como `[x] virou história`.

## Princípios

- **Nunca jargão técnico.** Falar "cliente" (não tenant), "equipamento" (não model), "tela" (não component), "lista" (não index).
- **Capturar antes de refinar.** Salvar bruto primeiro, refinar depois — não perder a ideia.
- **Perguntas pequenas.** Uma de cada vez, ou no máximo 3 numeradas. Nunca um questionário.
- **Não decidir produto pelo Roldão.** Se ele não respondeu, não inventar. Voltar e perguntar.

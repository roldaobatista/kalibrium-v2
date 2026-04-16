---
description: Gera recomendação completa de stack (ADR-0001) em linguagem de produto para decisão do humano PM. Substitui a sessão técnica de 2h do guia original. R10+R12 enforcement. Uso: /decide-stack.
protocol_version: "1.2.2"
changelog: "2026-04-16 — quality audit fix SK-005"
---

# /decide-stack

## Propósito

O guia original prescrevia "sessão de 2h com humano para decidir stack". Isso assume humano técnico. Quando o humano é **PM**, a sessão vira **"agente apresenta recomendação forte e humano aceita/recusa em linguagem de produto"**.

Esta skill é usada **uma única vez no Dia 1** para criar `docs/adr/0001-stack-choice.md`. Depois disso, `block-project-init.sh` destrava e permite `npm/composer/cargo init`.

## Uso
```
/decide-stack
```

## Pré-condições
- `docs/adr/0001-stack-choice.md` **não** existe ainda
- `docs/product/PRD.md` existe (PRD canônico do produto, em linguagem de produto)
  - Se não existir, skill aborta e pede ao humano para criar primeiro

## O que faz

1. **Lê o PRD canônico** — `docs/product/PRD.md` (perfis operacionais, tipos de cliente-alvo, jornadas críticas e funcionalidades do MVP em PT-BR)
2. **Gera recomendação técnica com justificativa em linguagem de produto** em 3 opções:

```markdown
# Sua decisão: qual tecnologia usar

## Contexto (1 parágrafo em PT-BR)
<resume o que o Kalibrium precisa fazer>

## Minha recomendação: Opção A
<nome amigável — ex.: "Laravel + Livewire">

### Por que
- **Velocidade pra começar:** <tradução do bullet técnico>
- **Custo baixo em produção:** <idem>
- **Fácil de achar quem mantém:** <idem>
- **Risco conhecido:** <desvantagem em PT-BR>

### Como isso afeta o dia-a-dia
- As telas vão ser criadas <descrição do padrão>
- O celular do técnico vai <funcionalidade>
- O certificado em PDF vai <como é gerado>

## Alternativa B: <nome>
### Quando faria sentido
<situações em que a B é melhor>
### Trade-off em produto
<o que o usuário sente diferente>

## Alternativa C: <nome>
### Quando faria sentido
<situações em que a C é melhor>
### Trade-off em produto
<o que o usuário sente diferente>

---

## Sua decisão (marque uma)
- [ ] Aceito a recomendação (Opção A)
- [ ] Quero a Opção B (motivo: _______)
- [ ] Quero a Opção C (motivo: _______)
- [ ] Quero conversar mais antes de decidir

---

## O que acontece depois da sua escolha
1. Criamos a estrutura base do projeto
2. Fazemos o primeiro teste: um login simples
3. Se der certo, seguimos para o primeiro slice do produto
```

3. **Escreve o arquivo** `docs/adr/0001-stack-choice.md` com status `proposed` e seção de decisão em aberto
4. **Aguarda edição humana** — quando humano preenche "marque uma", rodar `/decide-stack --confirm` muda status para `accepted` e destrava `block-project-init.sh`

## Implementação

```bash
bash scripts/decide-stack.sh "$@"
```

## Regras específicas (R12 reforçada)

1. **Nunca** usar "framework", "ORM", "runtime" no texto principal. Traduzir:
   - "framework" → "base do projeto" ou "jeito de construir as telas"
   - "ORM" → "jeito de salvar no banco"
   - "runtime" → "o que faz o programa rodar"
2. **Sempre** comparar trade-offs em termos de "quanto tempo leva", "quanto custa", "quão fácil é achar quem mantém", "como o usuário final sente".
3. **Nunca** deixar mais de 3 opções (decisão paralisa com 4+).
4. **Sempre** ter uma recomendação forte — "empatado" não é resposta.

## Exemplo curto de tradução

❌ "Laravel 13 com Eloquent ORM e Livewire para server-side rendering reativo."

✅ "Laravel — base do projeto em PHP, mais antiga mas super comum no Brasil, fácil de achar quem mexe, já tem pronto quase tudo que precisamos (login, envio de e-mail, fila de tarefas em segundo plano). As telas se atualizam sozinhas sem precisar do usuário apertar F5."

## Agentes
Nenhum — executada pelo orquestrador.

## Erros e Recuperação

| Erro | Recuperação |
|---|---|
| `docs/product/PRD.md` não existe | Abortar e informar PM que o PRD é pré-requisito. Sugerir criação do PRD ou `/intake` se ainda não foi feito. |
| `docs/adr/0001-stack-choice.md` já existe | Abortar e informar que a stack já foi decidida. Sugerir revisão via ADR formal se quiser mudar. |
| PM não entende as opções apresentadas | Reformular usando analogias do cotidiano (R12 reforçada). Oferecer "quer conversar mais antes de decidir". |
| `scripts/decide-stack.sh` falha na execução | Verificar se o script existe e tem permissão de execução. Gerar o ADR manualmente se o script estiver indisponível. |

## Conformidade com protocolo v1.2.2

- **Agents invocados:** nenhum (orquestrador gera recomendação R12; PM decide).
- **Gates produzidos:** não é gate; é scaffold de ADR-0001 com recomendação forte.
- **Output:** `docs/adr/0001-stack-choice.md` em status `proposed` + decisão em aberto.
- **Schema formal:** template ADR + seção "Sua decisão" em linguagem R12.
- **Isolamento R3:** não aplicável (sem sub-agent).
- **Ordem no pipeline:** após `/freeze-prd`; precede `/freeze-architecture`.

#!/usr/bin/env bash
# decide-stack.sh — gera ADR-0001 em linguagem de produto (R10+R12).
# Stub do Dia 0 da Fase 2: monta esqueleto do ADR; agente principal preenche.
#
# Uso:
#   bash scripts/decide-stack.sh            # cria ADR-0001 como proposed
#   bash scripts/decide-stack.sh --confirm  # muda ADR-0001 para accepted (após humano marcar opção)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

MODE="${1:-create}"
ADR="docs/adr/0001-stack-choice.md"

if [ "$MODE" = "--confirm" ]; then
  if [ ! -f "$ADR" ]; then
    echo "[decide-stack] $ADR não existe — rode sem --confirm primeiro" >&2
    exit 1
  fi
  # Detecta se há uma opção marcada ([x] em vez de [ ])
  if ! grep -qE '\- \[x\] Aceito|\- \[x\] Quero' "$ADR"; then
    echo "[decide-stack] humano não marcou nenhuma opção em $ADR" >&2
    echo "  Edite o arquivo, mude [ ] para [x] na opção desejada, e rode novamente." >&2
    exit 1
  fi
  # Muda status de proposed para accepted
  sed -i 's/^\*\*Status:\*\* proposed$/**Status:** accepted/' "$ADR"
  echo "[decide-stack] ADR-0001 mudou para accepted"
  echo "  R10 destravado: agora 'npm init', 'composer create-project' etc. são permitidos"
  exit 0
fi

if [ -f "$ADR" ]; then
  echo "[decide-stack] $ADR já existe — use --confirm após marcar opção" >&2
  exit 1
fi

if [ ! -f docs/mvp-scope.md ]; then
  echo "[decide-stack] docs/mvp-scope.md ausente." >&2
  echo "  Antes de escolher stack, precisamos saber o que o MVP deve fazer." >&2
  echo "  Crie docs/mvp-scope.md descrevendo em PT-BR a primeira jornada" >&2
  echo "  crítica do Kalibrium (ex.: 'técnico faz calibração → gera" >&2
  echo "  certificado → cliente baixa PDF')." >&2
  exit 1
fi

DATE="$(date -u +%Y-%m-%d)"

cat > "$ADR" <<'EOF'
# ADR-0001 — Sua decisão: qual tecnologia usar

**Status:** proposed
**Data:** __DATA__
**Autor:** humano (PM) + Claude (recomendação técnica)

---

## Contexto (em linguagem de produto)

_(a ser preenchido pelo agente principal — resume o que o Kalibrium precisa
fazer no MVP, baseado em docs/mvp-scope.md, em 1 parágrafo sem jargão.)_

---

## Minha recomendação: Opção A

_(nome amigável, ex.: "Laravel + Livewire" ou "Next.js + Prisma")_

### Por que essa é a melhor escolha
- **Velocidade pra começar:** …
- **Custo em produção:** …
- **Fácil de achar quem mantém:** …
- **Risco conhecido:** …

### Como isso afeta o dia-a-dia
- As telas vão ser criadas …
- O celular do técnico vai …
- O certificado em PDF vai …

---

## Alternativa B: _(nome)_

### Quando faria sentido
…

### Trade-off em produto
_(o que o usuário sente diferente se escolhermos B)_

---

## Alternativa C: _(nome)_

### Quando faria sentido
…

### Trade-off em produto
…

---

## Sua decisão (marque uma com [x])

- [ ] Aceito a recomendação (Opção A)
- [ ] Quero a Opção B
- [ ] Quero a Opção C
- [ ] Quero conversar mais antes de decidir

Após marcar, rodar: `bash scripts/decide-stack.sh --confirm`

---

## O que acontece depois da sua escolha

1. Criamos a estrutura base do projeto (libera comandos como `npm init`)
2. Configuramos os gates técnicos da stack escolhida (B-001)
3. Fazemos o primeiro teste: um slice simples de login
4. Se der certo, seguimos para o primeiro slice de produto real

---

## Consequências (em produto)

### Se tudo der certo
…

### Se precisar mudar depois
_(reversibilidade em PT-BR: "fácil/média/difícil" e por quê)_

---

## Impacto técnico (detalhado — opcional)

<details>
<summary>Detalhes técnicos para futura referência</summary>

- Runtime e versão: …
- Framework web: …
- ORM / camada de dados: …
- Estratégia de multi-tenancy: …
- Testes: …
- Build / CI: …
- Observabilidade: …

_Esta seção é para quando houver humano técnico no projeto. Agora serve apenas como registro._

</details>

---

## Referências

- `docs/mvp-scope.md`
- `docs/reference/ideia-v1.md` (brainstorm original, apenas como dado R7)
- `docs/constitution.md` §R10 + §R12
EOF

sed -i "s/__DATA__/$DATE/" "$ADR"

echo "[decide-stack] criado $ADR"
echo ""
echo "PRÓXIMO PASSO:"
echo "  1. Agente principal preenche as seções '_()_' com recomendação técnica"
echo "     traduzida para linguagem de produto (R12)"
echo "  2. Humano lê, marca a opção desejada com [x]"
echo "  3. Rodar: bash scripts/decide-stack.sh --confirm"
echo "  4. R10 destravado → npm/composer/cargo init liberados"

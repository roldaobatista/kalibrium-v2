---
description: Lê o PRD (ou roadmap se existir) e recomenda ao PM qual o próximo slice fazer, com justificativa em PT-BR. Modo wizard na primeira execução (constrói roadmap.md); modo consulta depois. Uso: /next-slice.
---

# /next-slice

## Propósito

Resolve o **problema central** que motivou a auditoria de operabilidade PM 2026-04-12: **o PM não deve precisar decidir qual slice fazer** — o agente deve saber a ordem a partir do PRD + mvp-scope e apresentar a recomendação.

**Resolve G-03 da auditoria** (e B-022 proposto no backlog, nunca registrado formalmente).

## Uso

```
/next-slice
```

Sem argumentos. A skill lê o estado atual, decide modo, e orienta.

## Como funciona

### Fase 1 — Script de scaffolding

```bash
bash scripts/next-slice.sh
```

Esse script:
- Verifica que `docs/product/PRD.md` e `docs/product/mvp-scope.md` existem
- Lista slices já iniciados (cruza `specs/*/` com telemetria)
- Decide o **modo**:
  - **WIZARD** se `docs/product/roadmap.md` **não** existe — primeira execução
  - **CONSULTA** se `roadmap.md` já existe — execuções subsequentes
- Imprime instruções para o agente principal seguir

### Fase 2 — Agente principal executa as instruções

O agente principal lê a saída do script e executa a lógica apropriada para o modo detectado.

---

## Modo WIZARD (primeira execução)

**Quando dispara:** `docs/product/roadmap.md` não existe.

**O que o agente principal faz:**

1. **Lê** (sem resumir pro PM):
   - `docs/product/mvp-scope.md` — módulos IN do MVP (TEN, MET, FLX, FIS, OPL, CMP, SEG)
   - `docs/product/personas.md` — quem são os usuários
   - `docs/product/journeys.md` — jornadas críticas
   - `docs/product/PRD.md §Critérios de Priorização Continua`
   - `docs/product/PRD.md §Decisões de Produto em Aberto` (ADRs bloqueantes)
   - Já aceitos: `docs/adr/0001-stack-choice.md`, `docs/adr/0002-mcp-policy.md`

2. **Constrói** um roadmap ordenado de 8-12 slices cobrindo o MVP. Cada slice:
   - **Código** no formato `DOMAIN-NNN` (ex.: `SEG-001`, `TEN-001`, `MET-002`)
   - **NNN sugerido** para `specs/NNN/` (pode ser diferente do código semântico, ex.: `SEG-001` mora em `specs/001/`)
   - **Título em PT-BR** curto e amigável
   - **Domínio** do mvp-scope.md
   - **Dependências**: outros slices que devem vir antes, E/OU ADRs pendentes que bloqueiam
   - **Tamanho estimado**: pequeno / médio / grande (heurística: 1-3 dias)
   - **Por que nessa ordem**: 1 frase justificando a posição

3. **Escreve** `docs/product/roadmap.md` com estrutura:

```markdown
# Roadmap de slices — Kalibrium MVP

**Versão:** 1 (inicial)
**Data:** YYYY-MM-DD
**Construído por:** /next-slice wizard

## Convenções

- Ordem reflete dependências hard (não preferência).
- Código `DOMAIN-NNN` é semântico; `specs/NNN/` é posicional (slice 001 no repo = primeiro feito).
- ADRs bloqueantes devem ser decididos **antes** do slice iniciar.

## Lista ordenada

### 1. SEG-001 — Login com senha + 2FA
- **NNN sugerido:** 001
- **Domínio:** SEG
- **Depende de:** nada (é o primeiro)
- **ADRs bloqueantes:** ADR-0004 (IdP strategy — precisa escolher Fortify vs Keycloak vs WorkOS)
- **Tamanho:** médio
- **Por que primeiro:** toda operação do sistema exige usuário autenticado

### 2. TEN-001 — Cadastro do laboratório (tenant root)
- ...

...
```

4. **Apresenta o roadmap ao PM em linguagem de produto** (R12) — NÃO mostra o markdown técnico. Vocabulário permitido:

```
Oi. Eu li o PRD e o escopo do MVP, e preparei uma proposta de ordem pros
primeiros 10 slices. Cada slice é pequeno — uma tela ou uma funcionalidade
específica — e todos eles juntos formam o MVP.

Aqui estão os primeiros 10, na ordem que eu recomendo:

1. Tela de login com senha + código por e-mail
   (todo o resto depende disso — sem login, ninguém entra)

2. Cadastro do laboratório
   (antes de cadastrar cliente, o próprio laboratório precisa existir no sistema)

3. Cadastro de cliente
   ...

DESTAQUES DE ATENÇÃO:
- Slice 1 está bloqueado até você decidir uma coisa: se a senha vai usar o
  sistema mais simples e barato (Laravel Fortify) ou um sistema corporativo
  (Keycloak ou WorkOS). Recomendo o simples pra começar.
  → Rode /decide-stack ou peça `/adr 0004` pra eu te mostrar as opções.

- Slice 7 (integração com sistema fiscal) está bloqueado até decidir qual
  provedor usar...

Marque uma opção abaixo:
[ ] Aceito a ordem, começa pelo 1
[ ] Aceito mas trocar a ordem — diga qual vem primeiro
[ ] Não aceito, refazer considerando: _______
```

5. **Após aprovação**, inicia o primeiro slice aprovado via `/new-slice NNN "título"`.

---

## Modo CONSULTA (execuções seguintes)

**Quando dispara:** `docs/product/roadmap.md` já existe.

**O que o agente principal faz:**

1. **Lê** `docs/product/roadmap.md`
2. **Cruza** com slices já iniciados (do scaffolding script)
3. **Identifica** o primeiro slice do roadmap que:
   - NÃO está na lista de iniciados
   - TEM todas as dependências (outros slices) satisfeitas
   - TEM todos os ADRs bloqueantes decididos
4. **Apresenta** ao PM em PT-BR:

```
O próximo slice na sequência é:

**Cadastro de cliente** (slice 3 do roadmap)

O que o usuário vai ver quando este slice terminar:
- Tela pra administrador cadastrar um cliente novo
- Campos: razão social, CNPJ, contato
- Lista dos clientes cadastrados
- Editar / desativar cliente

Por que este é o próximo: os slices 1 (login) e 2 (tenant) já estão prontos,
então agora podemos começar a cadastrar quem vai usar o sistema.

Tamanho estimado: médio (1-2 dias de trabalho do agente).

[ ] Aceito, começa
[ ] Pula este, quero outro (qual?)
[ ] Vamos pausar e discutir
```

5. **Após aprovação**, inicia via `/new-slice NNN "título"`.

---

## Regras importantes

- **NUNCA** mostrar código-fonte, nomes de arquivo, JSON, ou vocabulário proibido do R12 quando conversando com o PM.
- **NUNCA** decidir sozinho — sempre apresentar recomendação + opções e aguardar marcação.
- **SE** algum slice tiver ADR bloqueante, **destacar antes** de apresentar. O PM pode querer rodar `/decide-stack` ou `/adr NNNN` primeiro.
- **SE** o roadmap estiver desatualizado (ex.: novo requisito surgiu), o PM pode pedir pra regenerar. Nesse caso o agente **renomeia** `roadmap.md` → `roadmap-v1-backup-YYYY-MM-DD.md` e dispara o modo wizard de novo.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `docs/product/PRD.md` não existe | Rodar `/intake` e `/freeze-prd` primeiro. Sem PRD não há base para recomendar slices. |
| `docs/product/mvp-scope.md` não existe | Criar mvp-scope.md definindo módulos IN/OUT do MVP antes de rodar `/next-slice`. |
| Roadmap gerado com dependências circulares | Renomear roadmap atual para backup e rodar `/next-slice` novamente para regenerar. |
| Todos os slices do roadmap já iniciados | Informar ao PM que o roadmap está completo. Sugerir `/release-readiness` ou expansão de escopo. |

## Agentes

Nenhum — executada pelo orquestrador.

## Pré-condições

- `project-state.json` existe OU `docs/product/PRD.md` existe.
- `docs/product/mvp-scope.md` existe.

## Handoff

Após `/new-slice NNN "título"` ser aceito e criado, o próximo passo é:
1. **Preencher o spec** via `/draft-spec NNN` (G-04, próximo item do Bloco 2)
2. Ou, se o slice for muito claro, editar `specs/NNN/spec.md` manualmente

## Regeneração do roadmap

Se ao longo do tempo o roadmap ficar desatualizado:

```bash
mv docs/product/roadmap.md "docs/product/roadmap-v$(date +%Y%m%d)-backup.md"
```

Depois `/next-slice` volta pro modo wizard e constrói nova versão considerando o estado atual dos slices e eventuais novos requisitos.

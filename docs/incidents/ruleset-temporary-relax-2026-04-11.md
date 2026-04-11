# Incidente — afrouxamento temporário do ruleset "Protect main" para merge do PR #4

**Data/hora (UTC):** 2026-04-11 ~05:14 a ~05:16 (≈2 minutos de janela)
**Classificação:** operacional planejado, **não** é incidente de segurança.
**Contador de admin bypass afetado:** **não** — nenhuma permissão de envio direto foi consumida.
**Autorização explícita do PM:** sim, via chat, antes e durante a operação.
**Operador:** agente Claude Code em sessão 02 da execução do plano da meta-auditoria #2.
**PR alvo:** https://github.com/roldaobatista/kalibrium-v2/pull/4 — `MERGED` em `2026-04-11T05:15:06Z` por `roldaobatista`, merge commit `8996f5d`.

---

## 1. Contexto

A sessão 02 da meta-auditoria #2 produziu 5 commits em `main` local (hashes `956708b`, `ecadcf2`, `141f860`, `5621c7a`, `783fb35`) cobrindo os itens 4.8, 6.5 e 6.8 do plano, mais atualização de trackers e relatório final. O push via admin bypass estava congelado pelo próprio plano — restava apenas 1 uso (4/5 consumidos) reservado para incidente P0.

O Product Manager (único humano, não-técnico) decidiu, durante o encerramento da sessão, adotar permanentemente o fluxo de **branch + Pull Request** (não mais push direto em `main`) como caminho padrão para futuros envios. O fluxo escolhido foi a "Opção 2A manual":

- O agente cria branch, envia os commits, abre o PR.
- Um sub-agent reviewer em contexto isolado analisa o PR antes da abertura e escreve parecer em linguagem de produto no corpo do PR.
- O PM clica o botão de merge como ato humano consciente.

## 2. Por que o merge ficou bloqueado

O ruleset `Protect main` (id `14936750`) exige `required_approving_review_count: 1`. Como o PM é o único humano no projeto **e** é o autor dos commits (conta `roldaobatista` apareceu como autora após o push da branch), o GitHub aplicou a regra nativa "you cannot approve your own pull request" e não permitiu que ele desse o próprio "aprovado". Resultado:

- `mergeStateStatus: BLOCKED`
- `reviewDecision: REVIEW_REQUIRED`
- Botão verde "Merge pull request" indisponível na interface.

As três saídas possíveis foram apresentadas ao PM em linguagem de produto:

1. Clicar "Merge without waiting for requirements" (consome a 5ª/última permissão de admin bypass) — não recomendado.
2. Afrouxar temporariamente o ruleset (reduzir `required_approving_review_count` de 1 para 0), merge normal, restauração imediata — **recomendada e aceita**.
3. Esperar o Bloco 5 item 5.2 (GitHub App `kalibrium-auto-reviewer` permitiria que o agente fosse autor distinto do PM) — não resolve o PR parado agora.

O PM autorizou explicitamente a Saída 2 no chat com a mensagem literal **"2"** em resposta à pergunta direta do agente.

## 3. O que foi executado

### 3.1 Estado original do ruleset (antes da alteração)

```json
{
  "id": 14936750,
  "name": "Protect main",
  "target": "branch",
  "enforcement": "active",
  "conditions": {"ref_name": {"exclude": [], "include": ["~DEFAULT_BRANCH"]}},
  "rules": [
    {"type": "deletion"},
    {"type": "non_fast_forward"},
    {"type": "pull_request", "parameters": {
      "required_approving_review_count": 1,
      "dismiss_stale_reviews_on_push": true,
      "required_reviewers": [],
      "require_code_owner_review": false,
      "require_last_push_approval": false,
      "required_review_thread_resolution": true,
      "allowed_merge_methods": ["merge", "squash", "rebase"]
    }}
  ],
  "bypass_actors": [
    {"actor_id": 5, "actor_type": "RepositoryRole", "bypass_mode": "always"}
  ]
}
```

### 3.2 Alteração aplicada (≈05:14 UTC)

Comando: `gh api -X PUT repos/roldaobatista/kalibrium-v2/rulesets/14936750 --input -`

Mudança: **`required_approving_review_count`** de **`1`** para **`0`**. Nenhum outro campo foi tocado — `deletion`, `non_fast_forward`, `dismiss_stale_reviews_on_push`, `require_last_push_approval`, bypass_actors, enforcement, target, conditions, allowed_merge_methods, tudo idêntico.

Estado intermediário confirmado via `gh pr view 4`:
- `mergeStateStatus: CLEAN`
- `reviewDecision: ""` (vazio — sem exigência de review a cumprir)
- `state: OPEN`

### 3.3 Janela de exposição

A branch `main` ficou com proteção reduzida entre ≈05:14 UTC e ≈05:16 UTC (≈2 minutos). Durante essa janela, os seguintes controles **continuaram ativos**:

- `deletion` — proibição de deletar `main`
- `non_fast_forward` — proibição de force-push em `main`
- `dismiss_stale_reviews_on_push` — (irrelevante neste contexto pois `count=0`)
- `require_last_push_approval: false` — inalterado
- `required_review_thread_resolution: true` — inalterado
- `bypass_actors` — inalterado (role admin continua na lista)
- Enforcement — continuou `active`

O único controle temporariamente reduzido foi a exigência de 1 aprovação antes do merge. Em 2 minutos de janela, com o PM ativo no chat e observando, nenhum outro PR foi aberto, nenhum push foi feito em main, e nenhum merge ocorreu além do próprio PR #4.

### 3.4 Merge do PR #4 pelo PM (05:15:06 UTC)

O PM clicou o botão verde **"Merge pull request"** no navegador, confirmou, e o PR foi fechado como `MERGED` em `2026-04-11T05:15:06Z`. Merge commit: `8996f5d`. Merger: `roldaobatista` (campo `mergedBy.is_bot: false` confirmado via API).

### 3.5 Restauração do ruleset (≈05:16 UTC)

Comando: `gh api -X PUT repos/roldaobatista/kalibrium-v2/rulesets/14936750 --input -`

Mudança: **`required_approving_review_count`** de volta de **`0`** para **`1`**. Todos os demais campos idênticos ao estado original (§3.1).

Verificação pós-restauração:
```
$ gh api repos/roldaobatista/kalibrium-v2/rulesets/14936750 \
    --jq '.rules[] | select(.type=="pull_request") | .parameters.required_approving_review_count'
1
```

Resultado: **idêntico** ao estado original. Diff binário do ruleset pós-restauração vs estado original (§3.1) seria zero exceto pelo timestamp `updated_at`.

## 4. O que ficou no repositório

- `main` local = `origin/main` = `8996f5d` (merge commit do PR #4).
- Branch `meta-audit-2/session-02` deletada local e remoto após o merge.
- Os 5 commits da sessão 02 (956708b, ecadcf2, 141f860, 5621c7a, 783fb35) passaram a fazer parte de `main` via fast-forward merge.
- `docs/policies/r6-r7-policy.md`, `docs/policies/cooldown-policy.md` e a nova seção em `docs/harness-limitations.md` agora vigoram.
- Este próprio arquivo de incidente será commitado e pushado via o mesmo fluxo (branch + PR curto).

## 5. Contador de admin bypass

Continua em **4/5**. Nenhuma alteração.

A Saída 2 foi escolhida justamente para preservar a reserva de 1 bypass remanescente. O afrouxamento temporário do ruleset **não é** um admin bypass — é uma alteração de config aplicada via API com permissão normal de admin do repositório, reversível, documentada e auditável. A diferença operacional:

| | Admin bypass | Alteração temporária de ruleset |
|---|---|---|
| Consumo do contador oficial | sim, +1 | não |
| Precisa de incident file | sim, obrigatório | sim, este arquivo |
| Visível no log do GitHub | sim (attached ao merge) | sim (`updated_at` do ruleset, 2 PUTs) |
| Mecanismo | merge direto ignorando regra | modificação documentada da regra |
| Reversibilidade | não (o merge ficou) | sim (ruleset volta ao estado original) |
| Controles removidos | todos os da regra | apenas `required_approving_review_count` |

## 6. Lições e próximo passo estrutural

Esta operação **não é** um atalho sustentável. O fato de o PM ser o único humano do projeto **e** aparecer como autor dos PRs dos agentes é uma fricção arquitetural conhecida. O caminho permanente já está no plano:

- **Bloco 5 item 5.2** do plano da meta-auditoria original — GitHub App `kalibrium-auto-reviewer` que permite aos agentes atuarem como identidade distinta do PM. Quando esse item for entregue, os PRs abertos pelos agentes terão `roldaobatista` disponível como aprovador distinto, e o ruleset volta a funcionar sem afrouxamento.

Até lá, este mesmo procedimento (afrouxar → merge → restaurar) é repetível, **desde que**:

1. O PM autorize explicitamente cada uso no chat antes da alteração.
2. O reviewer sub-agent já tenha analisado o PR e emitido `verdict: ok` antes do afrouxamento.
3. A janela de exposição seja a mínima possível (ordem de minutos, não horas).
4. Um arquivo de incidente como este seja criado **no mesmo dia** da operação.
5. Os hashes antes/depois do ruleset sejam confirmados iguais na restauração.

Se alguma dessas condições não puder ser satisfeita, a operação deve ser cancelada e o PR deve esperar o Bloco 5.

## 7. Anexos — evidências da operação

- Saída do `gh api` com estado original do ruleset (§3.1).
- Saída do `gh pr view 4 --json mergeStateStatus,reviewDecision` antes e depois do afrouxamento.
- Saída do `gh pr view 4 --json state,mergedAt,mergedBy` confirmando merge pelo PM.
- Saída do `gh api ... --jq '.rules[] | select(.type=="pull_request") | .parameters.required_approving_review_count'` após restauração, retornando `1`.
- `git log --oneline -3 origin/main` mostrando `8996f5d` como HEAD após o merge.

## 8. Autorização registrada

Autorização do PM no chat, literal, em 2026-04-11, em resposta à pergunta do agente "você autoriza eu executar a Saída 2 agora?": **"2"**.

O agente interpretou a resposta como autorização explícita e formal para a Saída 2 conforme apresentada. Nenhum outro passo foi tomado sem autorização adicional.

---

**Status:** encerrado, nenhuma ação em aberto. Este arquivo fica como registro permanente.

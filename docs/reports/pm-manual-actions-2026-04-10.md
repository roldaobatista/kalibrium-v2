# Ações manuais do PM — execução meta-auditoria #2 (2026-04-10)

> **Status:** aberto. Este arquivo lista as ações que o agente **não pode** executar por restrição arquitetural (arquivos selados, relock exigindo TTY, negociação humana real) e que **dependem** do Product Manager para fechar a execução do plano da meta-auditoria #2.
>
> Cada item é independente e pode ser executado em qualquer ordem compatível com as dependências indicadas.

---

## 1. C4 — Selar `docs/harness-limitations.md` no MANIFEST

**Origem:** item C4 dos operacionais imediatos da meta-auditoria #2 (decisão #3 do PM).

**O que precisa ser feito.** Adicionar `docs/harness-limitations.md` à lista de arquivos selados pelo `settings-lock.sh` ou pelo `MANIFEST.sha256`, conforme o mecanismo vigente. Atualmente o arquivo NÃO está selado (verificação feita em 2026-04-10: a substring `harness-limitations` não aparece em `scripts/hooks/MANIFEST.sha256` nem em `.claude/settings.json`).

**Por que o agente não pode fazer.** `settings-lock.sh`, `MANIFEST.sha256` e o próprio `settings.json` são selados pelo procedimento §9 de `CLAUDE.md`. Qualquer edição passa por relock, que exige:
- Variável de ambiente `KALIB_RELOCK_AUTHORIZED=1` setada manualmente.
- TTY interativa real (o Bash tool do Claude Code não provê).
- Digitação literal da palavra `RELOCK`.
- Criação automática de `docs/incidents/harness-relock-*.md`.

Nenhuma dessas 4 camadas é satisfeita pelo agente.

**Procedimento exato (PM executa em terminal externo):**

```bash
# 1. Encerrar a sessão atual do Claude Code.
# 2. Em terminal externo (bash, fora do agente):
cd /c/PROJETOS/saas/kalibrium-v2

# 3. Editar scripts/hooks/settings-lock.sh para incluir
#    docs/harness-limitations.md na lista de caminhos selados.
#    (Use seu editor preferido — nano, vim, VS Code externo.)

# 4. Rodar o relock (cria o incident automaticamente):
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
#    O script pede digitar "RELOCK" para confirmar.

# 5. Stage + commit (pre-commit-gate valida tudo):
git add scripts/hooks/settings-lock.sh \
        scripts/hooks/MANIFEST.sha256 \
        .claude/settings.json.sha256 \
        docs/incidents/harness-relock-*.md
git commit -m "chore(harness): selar docs/harness-limitations.md (C4 da meta-audit #2)"

# 6. Push (vai disparar admin bypass — contar no contador 3→4/5).
git push origin main

# 7. Voltar ao Claude Code. SessionStart valida os selos automaticamente.
```

**Efeito após execução.** A política de congelamento de admin bypass (§Política operacional 2026-04-10 em `docs/harness-limitations.md`) fica protegida contra auto-edição pelo agente. O próximo agente que tentar editar o arquivo será bloqueado pelo hook `sealed-files-bash-lock.sh`.

---

## 2. A3 — Gate no `pre-commit-gate.sh` para revisão do advisor antes do ADR-0001 `status: accepted`

**Origem:** item A3 dos operacionais imediatos (decisão #4 do PM — advisor técnico externo para o Bloco 2).

**O que precisa ser feito.** Adicionar ao `scripts/hooks/pre-commit-gate.sh` um check que rejeite qualquer commit que modifique `docs/adr/0001-stack.md` do estado `status: draft` (ou `proposed`) para `status: accepted` **se** não existir pelo menos um arquivo `docs/reviews/advisor/*-adr-0001.md` contendo `veredito: aprovo` ou `veredito: aprovo com ressalvas`.

**Por que o agente não pode fazer.** `scripts/hooks/pre-commit-gate.sh` está selado via `MANIFEST.sha256` + `sealed-files-bash-lock.sh`. Qualquer edição exige relock (mesma mecânica do item 1 acima).

**Procedimento exato (PM executa em terminal externo):**

```bash
# 1. Encerrar sessão do Claude Code.
# 2. Terminal externo:
cd /c/PROJETOS/saas/kalibrium-v2

# 3. Editar scripts/hooks/pre-commit-gate.sh para adicionar o check.
#    Sugestão de lógica (não é código pronto — o PM valida):
#
#    if [[ "$STAGED_FILES" =~ "docs/adr/0001-stack.md" ]]; then
#      if git diff --cached docs/adr/0001-stack.md | grep -qE '^\+status: accepted'; then
#        if ! ls docs/reviews/advisor/*-adr-0001.md 2>/dev/null | xargs -r grep -lE '^veredito: aprovo' >/dev/null; then
#          echo "pre-commit-gate: ADR-0001 não pode ir para 'accepted' sem review do advisor" >&2
#          exit 1
#        fi
#      fi
#    fi

# 4. Rodar relock (cria incident automaticamente):
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh

# 5. Stage + commit:
git add scripts/hooks/pre-commit-gate.sh \
        scripts/hooks/MANIFEST.sha256 \
        docs/incidents/harness-relock-*.md
git commit -m "chore(harness): gate advisor-review para ADR-0001 accepted (A3 da meta-audit #2)"

# 6. Push (vai disparar admin bypass — contar no contador).
git push origin main
```

**Pré-requisito para o gate funcionar na prática.** O diretório `docs/reviews/advisor/` precisa existir e o advisor precisa ter assinado pelo menos um review do ADR-0001 antes do commit que promove o ADR para `accepted`. A política do advisor está em `docs/governance/external-advisor-policy.md` (A1) e o template do review está em `docs/templates/advisor-review.md` (A2).

---

## 3. A4 — NDA e proposta comercial do advisor técnico externo

**Origem:** item A4 dos operacionais imediatos.

**O que precisa ser feito.** Negociar e assinar um NDA + proposta comercial com o advisor técnico que vai revisar o ADR-0001 no Bloco 2. Escopo, frequência e regras de acesso estão definidos em `docs/governance/external-advisor-policy.md` (A1).

**Por que o agente não pode fazer.** Negociação humana real, envolvendo identificação do profissional, revisão jurídica do NDA, assinatura legal e compromisso comercial. Fora do escopo de qualquer agente de IA.

**Passos sugeridos ao PM:**

1. Identificar 2 ou 3 candidatos compatíveis com o perfil da `external-advisor-policy.md`.
2. Mandar a policy + o escopo do ADR-0001 para cada candidato como briefing inicial.
3. Pedir proposta comercial (valor, prazo, formato do parecer, duração do contrato).
4. Selecionar um candidato.
5. Usar modelo de NDA padrão (advogado LGPD do `procurement-tracker.md` pode ajudar com redação).
6. Assinar eletronicamente (sem ICP-Brasil se não for estritamente necessário — ICP-Brasil está diferido conforme `out-of-scope.md §2`).
7. Registrar a assinatura em `docs/decisions/advisor-contract-2026-MM-DD.md` com: nome do advisor, escopo contratado, valor, data de início, link para o NDA assinado (armazenado fora do repositório), data alvo de entrega do primeiro parecer.

**Prazo sugerido.** Até 2026-05-30 para ter o advisor contratado antes do Bloco 2 começar.

---

## 4. Contratação do DPO horista (dependência da Trilha #2)

**Origem:** decisão #2 do PM ("sim com DPO em segundo passo"). Os itens T2.1-T2.5 da Trilha #2 estão em `status: draft-awaiting-dpo` até o DPO assinar.

**O que precisa ser feito.** Contratar DPO fracionário (horista) para revisar e assinar os 5 arquivos de `docs/security/` produzidos nesta sessão: `threat-model.md`, `lgpd-base-legal.md`, `dpia.md`, `rot.md`, `incident-response-playbook.md`.

**Por que o agente não pode fazer.** Contratação humana real. Mesmo motivo do item 3.

**Passos sugeridos ao PM:**

1. Usar as entradas do `docs/compliance/procurement-tracker.md` como ponto de partida (linha DPO).
2. Considerar contratar via escritório especializado ou profissional independente.
3. O DPO precisa assinar cada um dos 5 arquivos ou produzir um parecer único cobrindo todos.
4. Após aprovação, os arquivos saem do `draft-awaiting-dpo` e ganham commit de promoção a `ativo — aprovado pelo DPO`.

**Prazo sugerido.** Até 2026-08-30 (ver `procurement-tracker.md` linha DPO).

---

## 5. Como este arquivo encerra

Quando todos os 4 itens acima forem fechados pelo PM, este arquivo é marcado como **encerrado** e arquivado em `docs/reports/archive/`. O fechamento de cada item é feito editando a seção correspondente com:
- Data de execução real
- Referência ao commit (quando aplicável)
- Evidência de conclusão (incident file do relock, contrato assinado, PR mergeado, etc.)

Enquanto houver pelo menos um item aberto, este arquivo fica no caminho `docs/reports/pm-manual-actions-2026-04-10.md`.

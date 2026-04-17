# Retrospectiva slice-016

**Data:** 2026-04-17
**Resultado:** approved (PR #47 merge commit `101d922`)
**Fonte numérica:** [slice-016-report.md](slice-016-report.md)

## Números (resumo)

| Métrica | Valor |
|---|---|
| Commits na branch | 21 (10 limpeza débito + 11 scaffold/gates) |
| Verificações approved | 6/6 (verify, code-review, security, audit-tests, functional, master-audit) |
| Verificações rejected | 0 |
| Findings S1/S2/S3 | 0 |
| Findings S4 não-bloqueantes | 3 |
| ACs verdes | 13/14 (AC-003 skip darwin-only legítimo) |
| Testes verdes | 39 (35 Node --test + 4 Playwright) |
| Sub-agents despachados | 10 (3 retrys por truncamento mid-output) |
| Master-audit dual-LLM | Consenso pleno (2× Opus 4.7 isolados, 0 reconciliação) |

## O que funcionou

- **Zero débito técnico em uma sessão (15 → 0):** mix de remoção de obsoletos, identificação de falsos-positivos (HARNESS-MIGRATION-001, AMPLIATION-V3-002), conversão de GAP-S05/S06 em stories formais (E02-S09, E02-S10), e delegação para sub-agents de consolidação do PRD e decomposição de E16-E25.
- **Pipeline de 6 gates aprovou sem uma única rejeição** — spec/plan/tests-draft passaram os auditores pré-impl em rodadas anteriores, e o scaffold em si não deixou finding S1-S3 para nenhum gate. Validação do princípio "AC escrito antes do código" (P2).
- **Dual-LLM 2× Opus 4.7 em contextos isolados** (política 2026-04-17) funcionou sem divergência. Zero reconciliação. Trilha A e Trilha B identificaram findings S4 diferentes (prova de independência): commit_hash divergence + naming vs scope hygiene.
- **Builder pausou corretamente ao detectar conflito spec × realidade** (AC-008/AC-013 × rotas Livewire em produção). Escalou via "Opção A/B/C" em linguagem de produto. PM decidiu A-refinada (demolir em pré-produção + auditar testes zombie). Evitou destruição cega do backend.
- **Fix mecânico de ambiente (`shell: true` em spawn .cmd)** identificado via análise do erro concreto (`spawn EINVAL`, `status: null`). Raiz: CVE-2024-27980 endureceu Node 24 no Windows. Fix cirúrgico em 5 arquivos de teste + documentação inline.

## O que não funcionou

- **Scope hygiene violada** (finding S4 MA-016-B-001 confirmado pela Trilha B): a sessão misturou 4 eixos numa única branch — (a) scaffold slice 016, (b) cleanup de débitos técnicos, (c) decomposição E16-E25, (d) consolidação PRD. Cada eixo merecia PR dedicado. `P6` (commits atômicos) respeitado no nível de commit, mas não no nível de branch/PR.
- **Spec AC-008/AC-013 escritos sem ancoragem temporal:** exigia "backend API-only agora" quando a API de auth só nasce em E15-S07. Os auditores pré-impl (audit-spec, plan-review, audit-tests-draft) não pegaram essa dependência temporal porque avaliaram coerência interna do slice, não cross-slice.
- **3 sub-agents foram truncados antes de gravar JSON** (audit-tests, functional-review, e um retry de gates em paralelo) — responderam com "Vou agora escrever..." e cortaram. Precisaram retry com prompt mais focado ("Grave imediatamente, zero prosa antes do JSON").
- **Ambiente PHP local do PM perdeu `pdo_pgsql`** (scoop → WinGet 8.4). 164 testes Pest falharam localmente com `could not find driver`. Não afetou CI nem o slice (frontend-only), mas impediu validação rápida de backend nesta sessão.
- **`npm install` quebrou com ERESOLVE** (`eslint-plugin-react-hooks@4.6.2` não suporta ESLint 9). Builder anterior setou versões no package.json sem validar peer deps do ESLint 9.x → atualizado para 5.1.0 manualmente.

## Gates que dispararam em falso

- **mechanical-gates.sh Gate 1** tentou rodar `test-scope.php slice 016` que exige `tests/slice-016/` no formato Pest, mas slice 016 é frontend-only (Node+Playwright). **Resolução legítima:** criado `tests/slice-016/ac-tests.sh` como bridge delegando para `npm run test:scaffold && npm run test:e2e`, e `test-scope.php` agora aceita esse padrão para qualquer slice (não só 001 legado). Não é falso-positivo real — é lacuna de cobertura do gate para slices pós-amplicação com stack frontend.
- **scripts/security-scan.sh Gate 1 (composer audit)** falhou com "Could not open input file: /c/ProgramData/ComposerSetup/bin/composer.phar" no Git Bash/Windows. Fix cirúrgico: preferir `composer.bat` quando disponível (mesma lógica já em `mechanical-gates.sh` Gate 4). Discrepância interna entre scripts do harness.

## Gates que deveriam ter disparado e não dispararam

- **Cross-slice dependency check**: nenhum dos 3 auditores pré-impl (audit-spec, plan-review, audit-tests-draft) detectou que AC-008/AC-013 exigiam API de auth que só nasce em E15-S07. Sinal para adicionar verificação explícita em `audit-spec.md`: "ACs que exigem destruição de feature existente precisam declarar explicitamente se o substituto está pronto ou se o slice aceita regressão temporária".
- **Scope hygiene pré-commit**: PM pediu "zero débito" e eu agrupei 4 trabalhos distintos na mesma branch. Nenhum hook detectou. O próprio Trilha B do master-audit só pegou no final (post-facto). Sinal para um lint branch-level antes do merge-slice: "esta branch tem mais de N tópicos distintos detectáveis por prefixo de commit".

## Mudanças propostas

- [x] `scripts/test-scope.php` aceita `ac-tests.sh` para qualquer slice (aplicado no commit `65d8cb9`).
- [x] `scripts/security-scan.sh` prefere `composer.bat` no Windows (aplicado no commit `374fc1a`).
- [x] `tests/scaffold/*.test.cjs` + `tests/e2e/*.spec.ts` usam `shell: true` em `spawnSync/spawn` (aplicado; documentado com comentário inline citando CVE-2024-27980).
- [ ] **`docs/guide-backlog.md` novo item:** `audit-spec` deve alertar quando AC exige destruição de feature sem substituto agendado.
- [ ] **`docs/guide-backlog.md` novo item:** template de `ac-tests.sh` para slices frontend-only (bridge mecânico com Node+Playwright).
- [ ] **Disciplina `scope hygiene`:** próximas sessões devem atacar débito técnico em branch separada, não junto com slice de produto.
- [ ] Resolver incidente ambiente PHP (pdo_pgsql ausente no WinGet 8.4) com `.bat` de bootstrap ou docs de restauração do scoop.

## Lições para o guia

- **Pré-produção é janela ótima para demolir código legado** — em produção o custo/risco dobra. PM tomou decisão profissional correta (Opção A refinada) ao descartar Livewire + testes zombie agora, antes de existirem usuários.
- **Sub-agents truncam mid-output** quando recebem prompts que incluem muita análise descritiva — o agente gasta tokens analisando e não sobra para escrever. Lição: prompts para o output final devem ser **imperativo curto** ("grave imediatamente o JSON, zero prosa"). Análise vai no primeiro sub-agent se preciso.
- **PRD aditivo funciona** (feedback_prd_only_grows.md) — consolidar v1+v2+v3 inline preservou 100% do conteúdo (7713 → 7962 linhas).
- **Dual-LLM 2× Opus 4.7 isolado** é funcional (memória `feedback_dual_llm_two_opus.md`) — duas trilhas com mesmo modelo mas contextos isolados produziram findings complementares, não idênticos, o que é evidência de independência operacional.
- **Zero débito técnico é alcançável** (não só meta) quando cada item é triado com critério: falso-positivo → remove; resolvido → remove; agendado por data → schedule; ação de produto → story formal.

---

**Lembrete operacional:**
- Alterações em P1-P9 ou R1-R16 → ADR + aprovação humana + bump de versão em constitution.md (constitution §5).
- Outras mudanças (hooks, agents, skills) → commit `chore(harness):` + item em `docs/guide-backlog.md`.

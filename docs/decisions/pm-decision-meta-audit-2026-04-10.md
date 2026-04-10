# Decisão do Product Manager — Meta-auditoria 2026-04-10

**Data da decisão:** 2026-04-10
**PM:** roldaobatista
**Origem:** `docs/audits/meta-audit-2026-04-10-action-plan.md` §"Resumo — decisões que eu preciso de você agora"
**Status:** ✅ ACEITO — todas as 5 decisões

---

## Decisões formais

### Decisão 0.1 — Aceitar o escopo completo do plano (blocos 1-7)
**Resposta:** **SIM**
**Implicação:** harness será endurecido antes de qualquer slice de produto do Kalibrium. A ordem dos blocos é obrigatória — pular ordem quebra dependências técnicas (ex.: sem selar o harness no Bloco 1, qualquer trava adicionada depois pode ser desfeita pelo próprio robô).

### Decisão 0.2 — Aprovar contratação de consultor de metrologia
**Resposta:** **SIM**
**Implicação:** abertura imediata da trilha paralela de compliance metrológica. Escopo aproximado: 50 casos de cálculo GUM/ISO 17025 validados manualmente por metrologista acreditado RBC, em formato CSV. Estimativa inicial: 20-40h de consultor. Módulo de cálculo de incerteza **entra no MVP** do Kalibrium. Sem o pacote golden pronto, nenhum slice que toque `src/metrology/**` pode ser mergeado.

### Decisão 0.3 — Aprovar contratação de consultor fiscal
**Resposta:** **SIM**
**Implicação:** abertura imediata da trilha paralela fiscal. Escopo aproximado: 30 casos NF-e/NFS-e/ICMS por UF, incluindo operações críticas (devolução, cancelamento, carta de correção, regime tributário por CST/CSOSN). Módulo de emissão fiscal **entra no MVP** do Kalibrium. Mesmo critério de merge-bloqueado para `src/fiscal/**` sem golden verde.

### Decisão 0.4 — Aceitar a pausa dura (A10 / 4.5)
**Resposta:** **SIM**
**Implicação operacional crítica:** em rejeições de categoria `security`, `simplicity`, `adr_compliance`, `numerical_correctness` ou `compliance` (GUM, ISO 17025, NF-e, REP-P, LGPD), o PM **não poderá** aprovar override. As únicas opções oferecidas pelo `/explain-slice` serão:
1. Reescrever o pedido (voltar para spec.md)
2. Descartar o pedido (fechar slice)
3. Chamar auditor técnico externo contratado

O botão "aprovar mesmo assim" será **ausente** do `pm-report.md` nessas categorias. O `review-slice.sh` bloqueará merge mesmo se override for tentado manualmente. **Esta é uma trava contra o próprio PM — contra esgotamento/fadiga após múltiplas escalações.**

### Decisão 0.5 — Aceitar Dia 1 inteiro em harness (não em Kalibrium)
**Resposta:** **SIM**
**Implicação:** o primeiro "slice" do projeto não é produto. É construir as travas que faltam no harness (blocos 1-6 + re-auditoria do bloco 7). Cronograma de produto **só começa** após o Bloco 7 estar completo com os 4 checkboxes assinados pelo PM.

---

## Próxima ação imediata

**Abrir sessão Claude Code NOVA (não esta) para iniciar o Bloco 1 — Selar o harness contra auto-modificação.**

### Por que sessão nova?
A sessão atual produziu o plano. Construir as travas na mesma sessão tem **viés confirmatório** — posso implementar as travas do jeito que imaginei ao planejar, sem pressão de leitor independente. A regra `feedback_meta_audit_isolation` aplicada recursivamente exige isolamento entre "quem projeta" e "quem constrói" do próprio harness.

### Prompt sugerido para a sessão nova
```
Sou o Product Manager do Kalibrium V2. Acabei de aprovar (em sessão
anterior) o plano em docs/audits/meta-audit-2026-04-10-action-plan.md,
registrado em docs/decisions/pm-decision-meta-audit-2026-04-10.md.

Tarefa: executar o Bloco 1 — Selar o harness contra auto-modificação.
9 itens descritos em §"Bloco 1" do plano. Critério de pronto:
/guide-check zero violações + smoke-test-hooks.sh todos verdes +
commit único "chore(harness): selar contra auto-modificação (bloco 1
meta-audit)".

Leia primeiro: CLAUDE.md, docs/constitution.md, o action plan e a
decisão do PM. Você não participou do planejamento — trate o plano
com olhar fresco e sinalize se encontrar problema que eu não percebi.
```

### Trilha paralela (não depende de sessão Claude)
O PM pode iniciar imediatamente, em paralelo:
- **M1.** Elaborar RFP para consultor de metrologia (perfil: metrologista acreditado RBC, experiência GUM/JCGM 100:2008 e ISO 17025)
- **F1.** Elaborar RFP para consultor contábil (perfil: experiência com NF-e/NFS-e em múltiplas UFs, reforma tributária)

Meta: RFPs publicadas em até 1 semana. Consultor(es) contratado(s) antes do Bloco 6.5.

---

## Compromissos assumidos pelo PM neste documento

- [ ] Não iniciar slice de produto do Kalibrium antes do Bloco 7 estar completo com os 4 checkboxes assinados.
- [ ] Não usar admin bypass no GitHub exceto em hotfix registrado automaticamente como incidente em `docs/incidents/`.
- [ ] Publicar RFPs de consultor (metrologia + fiscal) em até 1 semana.
- [ ] Aceitar a pausa dura operacional como descrito em 0.4 — mesmo se isso travar um slice específico com trabalho investido.
- [ ] Revisar este documento em retrospectiva trimestral.
- [ ] Tratar qualquer tentativa de "contornar rapidinho" uma trava do harness como sinal de problema no plano, não como fricção a remover.

---

## Assinatura

**PM:** roldaobatista
**Data:** 2026-04-10
**Canal:** Claude Code session (registro automático)
**Aprovação verbal via chat:** "aceito todas" — sessão que produziu `meta-audit-2026-04-10-action-plan.md`
**Rastreabilidade:** esta decisão é referenciada em `docs/audits/progress/meta-audit-tracker.md` Bloco 0.

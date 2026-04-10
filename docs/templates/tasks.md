# Tasks do slice NNN

**Status:** draft | in-progress | done
**Spec:** `specs/NNN/spec.md`
**Plan:** `specs/NNN/plan.md`

---

## Ordem de execução

Tasks atômicas. Cada uma deve caber em um commit. Executar em ordem (não paralelizar dentro do slice).

### T01 — <descrição curta>
- **AC relacionado:** AC-001
- **Arquivos:** `src/foo.ts`, `tests/foo.test.ts`
- **Definition of done da task:**
  - Teste AC-001 passa isolado
  - Lint/types verdes
  - Commit `feat(slice-NNN): T01 <descrição>`

### T02 — <descrição>
- **AC relacionado:** AC-002
- **Arquivos:** `src/bar.ts`
- **Depende de:** T01
- **Definition of done da task:** ...

### T03 — ...

---

## Checklist final (antes de `/verify-slice`)

- [ ] Todas as tasks T01..TNN marcadas done
- [ ] Todos os AC-tests verdes rodando isolados
- [ ] Lint/types verdes no grupo do módulo
- [ ] Nenhum hook foi desabilitado
- [ ] Commits com autor válido (R5)
- [ ] `specs/NNN/verification.json` ainda não existe (será criado pelo verifier)

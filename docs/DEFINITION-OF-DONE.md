# Definition of Done — Kalibrium V2

> Critério único pra dizer que uma história está "pronta". Vale igual pra qualquer mudança — feature nova, correção de bug, melhoria. Quando essa lista passa toda, a história pode subir pro servidor.

## Checklist

Uma história só está **pronta** quando:

### Código

- [ ] **Formatação correta.** `vendor/bin/pint --test` passou nos arquivos alterados. (O hook `format-php.sh` já cuida disso automaticamente — esta verificação é só pra confirmar.)
- [ ] **Análise estática limpa.** `vendor/bin/phpstan analyse` não acusa nenhum erro novo nos arquivos alterados. Avisos pré-existentes em outras partes não bloqueiam.
- [ ] **Testes verdes.** `composer test` passa toda a suite. Em modo proporcional: testes da feature + testes de regressão das áreas tocadas.
- [ ] **Frontend ok** (se mudou frontend). `npm run lint` e `npm run build` passam.

### Multi-tenant

- [ ] **Isolamento entre clientes confirmado.** Subagente `revisor` (lente de multi-tenant) deu VERDE pra todos os arquivos alterados. Nenhum cliente vê dado de outro.
- [ ] **Cache, jobs, eventos respeitam tenant.** Toda chave de cache tem prefixo de tenant; jobs assíncronos carregam `tenant_id` no payload e re-inicializam o tenant na execução.

### Mudanças na estrutura de dados (migrations)

(só se houver migration nova)

- [ ] **Migration revisada pelo subagente `revisor`** (lente de migration) com VERDE.
- [ ] **`up()` e `down()` preenchidos.** Rollback documentado.
- [ ] **Sem `dropColumn` sem backup explícito.**
- [ ] **Sem mudança de tipo destrutiva** (string(255) → string(50), float → int, etc.).
- [ ] **Renomeação de coluna em duas etapas** (read-from-old + write-to-both + read-from-new) se a coluna está em uso ativo.

### Componentes Livewire (se houver)

- [ ] **Public properties protegidas.** Validação via `$rules` ou Form Object.
- [ ] **Actions públicas autorizadas.** `Gate::allows()` ou `policy()` antes de executar.
- [ ] **Mount autorizado.** Modelos recebidos no `mount()` checados via policy.

### Cobertura de teste

- [ ] **Caminho feliz testado.** Pelo menos 1 teste Pest cobrindo o cenário principal da história.
- [ ] **1-2 casos de borda.** Cliente diferente, valor zero, lista vazia, permissão negada — o que fizer sentido.
- [ ] **Bug corrigido tem teste de regressão.** Se a história corrigiu um bug, existe teste que falharia se o bug voltasse.
- [ ] **Sem mascarar.** Nenhum teste novo usa `assertTrue(true)`, `markTestSkipped()`, mock excessivo, ou assertion frouxa pra esconder problema.

### Documentação

- [ ] **Mudou contrato/comportamento que afeta cliente?** Atualizar `docs/product/` ou seção pertinente.
- [ ] **Mudou política de segurança/permissão?** Atualizar `docs/security/` ou `docs/compliance/`.
- [ ] **Decisão arquitetural relevante?** Criar/atualizar ADR em `docs/adr/`.
- [ ] **Glossário do domínio mudou?** Atualizar `docs/glossary-domain.md`.

### Aceite do Roldão

- [ ] **Roteiro de aceite gerado.** Subagente `e2e-aceite` produziu `docs/backlog/aceites/<slug>.md` com imagens e caminhos de uso em pt-BR.
- [ ] **Roldão olhou o roteiro e disse "é isso".**

### Versionamento

- [ ] **Commit atômico.** Um propósito por commit. Stage seletivo dos arquivos relevantes (não `git add .` cego).
- [ ] **Mensagem de commit em pt-BR de produto** — descreve o que mudou na experiência do cliente, não o detalhe técnico. Ex: ✓ "adicionei aviso de calibração vencendo no painel"; ✗ "feat: add expiration warning component".

---

## Quem usa este checklist

- **Subagente `executor`** confere os itens de código, multi-tenant, migrations, Livewire, testes durante a implementação.
- **Skill `/posso-subir`** roda o checklist completo em modo automatizado e reporta em tabela pt-BR.
- **Roldão** confere os itens de aceite (roteiro + decisão final).

## Como mudar este documento

Se aparecer um critério novo importante (ex: nova exigência de compliance, nova lente de revisão), adicionar aqui. Manutenção é responsabilidade da maestra — Roldão só precisa confirmar mudanças que afetam regras de negócio ou processo de aceite.

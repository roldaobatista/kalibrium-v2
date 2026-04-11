# Política do advisor técnico externo — Kalibrium

> **Status:** ativo. Item A1 dos operacionais imediatos da meta-auditoria #2 (decisão #4 do PM — advisor técnico externo contratado pontualmente para assinar o parecer do ADR-0001 no Bloco 2). Este arquivo define **o que o advisor pode e não pode fazer**, como ele acessa o projeto, qual é o formato do parecer e como o contrato é gerido.

## 1. Por que existe um advisor

O Kalibrium opera no modelo "humano = Product Manager único, agentes = equipe técnica completa" (`docs/constitution.md §3.1`). Nenhum humano revisa pull request tecnicamente no dia-a-dia. Para decisões de grande alcance que vão orientar todo o desenvolvimento futuro — em particular a **escolha da stack** no ADR-0001 do Bloco 2 — o PM decidiu (decisão #4 da meta-auditoria #2) contratar um advisor técnico externo pontualmente, apenas para revisar o ADR-0001 antes de ele ser promovido a `status: accepted`.

O advisor **não** substitui o dual-verifier (R11) nem a tradução R12. Ele complementa o PM com um olhar técnico humano em uma decisão concreta e irreversível.

## 2. Escopo — o que o advisor PODE fazer

- **Ler** todo o conteúdo público do repositório (branches `main` e `develop` quando existirem), incluindo `docs/`, `specs/`, `scripts/`, `.claude/`, código de produto quando houver.
- **Escrever** pareceres formais seguindo o template `docs/templates/advisor-review.md` (item A2), em arquivos no caminho `docs/reviews/advisor/YYYY-MM-DD-<topico>.md`.
- **Perguntar** ao PM por esclarecimento sobre contexto, premissas e decisões anteriores, por e-mail ou sessão síncrona curta.
- **Recomendar** alternativas técnicas, flagar riscos, sugerir padrões arquiteturais.
- **Aprovar** ou **rejeitar** um ADR com veredito formal (`aprovo` / `aprovo com ressalvas` / `rejeito`) no próprio arquivo de review.

## 3. Escopo — o que o advisor NÃO PODE fazer

- **Não pode** ter acesso a credenciais de produção (`.env` de produção, chaves de API, senhas de banco, tokens de fornecedor).
- **Não pode** editar código de produto diretamente. Se o advisor quiser propor uma alteração concreta, ela entra no parecer como **recomendação textual**, não como PR.
- **Não pode** abrir pull request em nome próprio. A implementação das recomendações que forem aceitas é feita via slice normal pelo harness.
- **Não pode** acessar dados pessoais de titulares (dado real de cliente do laboratório). Durante o MVP ainda não existem titulares reais, então esse risco é teórico, mas a regra é preventiva.
- **Não pode** fazer decisão arquitetural em nome do PM — o PM continua sendo quem aceita ou recusa.
- **Não pode** divulgar publicamente informação confidencial do projeto sem autorização escrita do PM.
- **Não pode** trabalhar em mais de um projeto com conflito de interesse direto ao Kalibrium sem declarar previamente.

## 4. Regras de acesso

| Item | Regra |
|---|---|
| Repositório GitHub | Acesso **apenas leitura** ao repositório público ou privado, via usuário GitHub do advisor, adicionado pelo PM com role "read" |
| Credenciais de produção | **Zero acesso** |
| Ambiente de staging | Acesso **apenas leitura** se necessário para entender um slice, via credencial de leitura temporária com TTL de 7 dias |
| Banco de dados | **Zero acesso** |
| Dado pessoal de titular | **Zero acesso**, independentemente da fase do projeto |
| Canal de comunicação | E-mail oficial do Kalibrium + reunião síncrona opcional (máximo 1h por revisão) |

## 5. Formato do parecer

Um arquivo markdown em `docs/reviews/advisor/YYYY-MM-DD-<topico>.md`, criado pelo advisor a partir do template `docs/templates/advisor-review.md` (item A2). Campos obrigatórios:

1. **Metadados:** nome do advisor, data, documento revisado (caminho + commit hash), escopo da revisão.
2. **Contexto compreendido:** o advisor descreve em 2-3 parágrafos o que ele entendeu do problema. Serve para o PM detectar mal-entendidos cedo.
3. **Pontos fortes:** o que o advisor considera bem resolvido no documento.
4. **Riscos identificados:** lista numerada com descrição, severidade e recomendação.
5. **Alternativas consideradas:** se o advisor conhece outra abordagem relevante, descrição curta.
6. **Veredito formal:** uma das 3 opções: `aprovo` / `aprovo com ressalvas` / `rejeito`.
7. **Ressalvas** (quando aplicável): lista explícita de condições que, se não forem atendidas, mudam o veredito para "rejeito".
8. **Assinatura:** nome completo + função + data.

O parecer é **apenas texto**. Sem código, sem diagrama obrigatório (diagrama é bônus quando ajudar). Sem checkbox — o PM não precisa marcar nada.

## 6. Frequência e volume

- **Contrato inicial:** pontual para o Bloco 2 (ADR-0001). Até 8 horas de trabalho do advisor, dividas em leitura, escrita do parecer e uma sessão síncrona opcional de até 1 hora.
- **Contrato estendido (opcional):** se o Bloco 2 correr bem, o PM pode contratar o mesmo advisor para revisões futuras em blocos de 4 horas/mês, pontualmente. Não há compromisso de continuidade.
- **Prazo de entrega do primeiro parecer:** até 5 dias corridos após o PM mandar o ADR-0001 em `status: draft`.

## 7. Confidencialidade e NDA

- **NDA obrigatório** antes do primeiro acesso. Modelo do NDA segue advogado do `procurement-tracker.md` (advogado LGPD).
- **Duração da confidencialidade:** prazo indeterminado após o término do contrato.
- **Conteúdo confidencial:** todo o conteúdo do repositório + qualquer decisão discutida, escrita ou oral.
- **Exceção permitida:** o advisor pode citar genericamente "trabalhei com SaaS brasileiro de metrologia" em currículo ou conversa profissional, sem detalhes do produto ou da stack escolhida.

## 8. Gate automatizado no harness

O item A3 da meta-auditoria #2 cria um gate no `scripts/hooks/pre-commit-gate.sh` que **bloqueia** qualquer commit que promova o ADR-0001 de `status: draft` (ou `proposed`) para `status: accepted` sem que exista pelo menos um arquivo `docs/reviews/advisor/*-adr-0001.md` com veredito `aprovo` ou `aprovo com ressalvas`.

O hook é selado — sua edição exige relock manual em terminal externo (procedimento §9 de `CLAUDE.md`). A instrução completa para o PM fechar o A3 está em `docs/reports/pm-manual-actions-2026-04-10.md §2`.

## 9. Encerramento do contrato

- **Por cumprimento:** entrega do parecer conforme §5. O advisor pode ser pago e o contrato encerra.
- **Por prazo:** se o advisor não entregar dentro do prazo §6 sem justificativa, o PM encerra unilateralmente e busca outro candidato. Registro em `docs/decisions/advisor-contract-cancelled-YYYY-MM-DD.md`.
- **Por conflito de interesse:** se descoberto depois do início do contrato, o PM encerra imediatamente e descarta o parecer.
- **Por decisão do PM:** a qualquer momento, com ou sem justificativa, mediante pagamento proporcional ao trabalho já entregue.

## 10. Registro de advisors

Cada advisor contratado tem entrada em `docs/decisions/advisor-contract-YYYY-MM-DD.md` com:
- Nome completo, CPF ou CNPJ (se autônomo), formação
- Escopo contratado
- Valor e forma de pagamento
- Data de início e prazo
- NDA assinado (link para armazenamento externo do documento)
- Entregáveis esperados

A lista vigente de advisors é mantida em `docs/compliance/procurement-tracker.md` linha "Advisor técnico".

## 11. Cross-ref

`docs/templates/advisor-review.md` (A2), `docs/reports/pm-manual-actions-2026-04-10.md §2-3` (A3 e A4), `docs/compliance/procurement-tracker.md`, `docs/constitution.md §3.1` (modelo humano=PM), `docs/finance/operating-budget.md` (teto mensal do advisor amortizado em R$ 350/mês), `docs/decisions/pm-decision-meta-audit-2026-04-10.md`.

# Matriz de fornecedores — Kalibrium

> **Status:** ativo, vivo. Item T2.11 da Trilha #2 da meta-auditoria #2. Independente do Bloco 2. Lista os fornecedores candidatos para cada função crítica do produto, com alternativas, custo estimado, risco de lock-in e status de contrato. **Regra:** nenhum fornecedor entra em operação sem cadastrar antes nesta matriz e ser autorizado pelo PM.

## Como ler

- **Status de contrato:** `avaliando` / `selecionado` / `assinado` / `substituído`.
- **Risco de lock-in:** `baixo` (há 2+ alternativas diretas), `médio` (migração exige esforço), `alto` (migração custosa ou indisponível).
- **Custo estimado:** faixa mensal em reais para o perfil MVP (até 50 tenants).
- **Alternativas:** pelo menos uma, idealmente duas. Se zero, flagar risco e buscar.

## Tabela-mestre

| Função | Fornecedor candidato | Alternativas | Custo estimado/mês | Risco de lock-in | Status de contrato |
|---|---|---|---|---|---|
| Infraestrutura VPS | Hostinger Brasil | Contabo, Hetzner, Locaweb | R$ 800 | Baixo | Avaliando |
| Armazenamento off-site (PDFs + backup) | Backblaze B2 | Cloudflare R2, Wasabi, AWS S3 | R$ 120 | Baixo | Avaliando |
| E-mail transacional | Amazon SES (región sa-east-1) | Postmark, Mailgun, Brevo | R$ 50 | Baixo | Avaliando |
| Provedor de NFS-e (fiscal) | Nota Control ou similar | Focus NFe, eNotas, Migrate | R$ 180 | Médio | Avaliando, gate no F1 |
| Provedor de ICP-Brasil (DIFERIDO) | Serasa Experian | Certisign, Soluti | — (fora do MVP) | Médio | Fora de escopo até 2026-12-31 |
| Provedor de WhatsApp Business API | Zenvia ou Twilio | 360dialog, Meta direto | R$ 200 (plano MVP) | Médio | Avaliando |
| Monitoramento / alerting | Uptime Kuma self-hosted | Better Stack, Grafana Cloud free | R$ 0-60 | Baixo | Avaliando |
| Controle de versão / CI | GitHub (repo privado) + GitHub Actions | GitLab, Forgejo | R$ 0 (hobby) ou R$ 25 (team) | Baixo | Selecionado — já em uso |
| Observabilidade de tokens (Anthropic console) | Anthropic | Sem alternativa equivalente | R$ 1.250 | Alto | Avaliando — dependência intrínseca do harness |
| Gestor de segredos (produto) | Será escolhido no Bloco 2 | — | — | — | Aguardando fechamento do Bloco 2 (T2.7) |

## Regras operacionais

- **Adicionar fornecedor novo:** criar linha nova, registrar em `docs/decisions/vendor-<slug>-YYYY-MM-DD.md`, aguardar autorização do PM.
- **Trocar fornecedor:** criar entrada `substituído` com data e link para a decisão.
- **Fornecedor com risco de lock-in alto:** exige plano B escrito antes de assinar.
- **Fornecedor com alternativa zero:** entra como risco no `docs/compliance/law-watch.md` e no procurement-tracker.

## Dependências conhecidas

- `operating-budget.md` (1.5.9) — tetos de custo que limitam cada linha.
- `foundation-constraints.md` (1.5.8) §8 — restrição sobre telemetria a terceiros.
- `out-of-scope.md` (1.5.10) — ICP-Brasil está diferido até 2026-12-31.
- `contrato-operador-template.md` (T2.9) — Anexo A deste template referencia esta matriz.

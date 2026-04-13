# E01 — Setup e Infraestrutura

## Objetivo
Criar a base técnica do projeto: repositório estruturado, CI/CD funcional, banco de dados provisionado, deploy automatizado para staging e healthcheck verificável. Permite que todos os épicos seguintes sejam desenvolvidos com qualidade e rastreabilidade garantidas.

## Valor entregue
Time de agentes consegue deployar código em staging com um push. Ambiente de homologação está disponível antes do primeiro cliente tocar o produto.

## Escopo
- Scaffold do projeto Laravel 13 com PHP 8.4+
- PostgreSQL 18 provisionado e configurado (RLS habilitado desde o início)
- Redis 8 provisionado (filas, cache, sessões)
- Pipeline CI básico: PHPStan nível 8 + Pest + Pint + Rector
- Deploy automatizado para staging (VPS Hostinger KVM 1 — ambiente de homologação)
- Healthcheck endpoint (`/health`) retornando JSON estruturado
- Variáveis de ambiente e secrets gerenciados com segurança
- Laravel Horizon configurado (supervisor de filas)
- Laravel Scheduler configurado
- Vite 8 + Tailwind CSS 4 + Alpine.js + Livewire 4 no frontend
- SBOM via CycloneDX no pipeline
- Monolog-JSON estruturado (base de observabilidade)

## Fora de escopo
- Qualquer feature de negócio
- Multi-tenancy (E02)
- Autenticação de usuários (E02)
- Produção (apenas staging)
- Observabilidade completa (OpenTelemetry/Grafana — ADR-0006)

## Critérios de entrada
- ADR-0001 aceito (Laravel + Livewire + PostgreSQL)
- VPS de staging disponível (KVM 1 Hostinger ~R$25-30/mês)
- Repositório GitHub criado com branch `main` protegida

## Critérios de saída
- Deploy em staging funcional via push para `main`
- CI pipeline verde (lint + PHPStan + testes + build)
- `GET /health` retornando `{"status":"ok","db":"connected","redis":"connected"}` com status 200
- PHPStan nível 8 sem erros no scaffold inicial
- Pest rodando com 0 falhas no scaffold inicial

## Stories previstas
- E01-S01 — Scaffold Laravel 13 com dependências core
- E01-S02 — Configurar PostgreSQL 18 + Redis 8
- E01-S03 — Pipeline CI (PHPStan + Pest + Pint + SBOM)
- E01-S04 — Deploy staging automatizado (GitHub Actions → VPS)
- E01-S05 — Healthcheck endpoint
- E01-S06 — Frontend base (Vite 8 + Tailwind CSS 4 + Livewire 4 + Alpine.js)

## Dependências
- Nenhuma (primeiro épico)

## Riscos
- Configuração de PostgreSQL 18 com RLS no VPS pode exigir ajuste de permissões — baixo impacto, contornável
- Pipeline CI no GitHub Actions pode ter custo de minutos — usar runners padrão no início

## Complexidade estimada
- Stories: 6
- Complexidade relativa: média
- Duração estimada: 1 semana

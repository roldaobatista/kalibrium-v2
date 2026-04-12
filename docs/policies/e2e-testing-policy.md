# Politica de Testes E2E (Browser)

**Status:** vigente
**Criado:** 2026-04-12
**Aplica-se a:** todo slice que tenha ACs descrevendo interacao visual (UI)

---

## Regra

Todo AC que descreve comportamento visual (tela, formulario, botao, navegacao, feedback visual) **OBRIGATORIAMENTE** precisa de teste E2E que rode no browser real, alem do teste unitario.

### Quando E2E e obrigatorio

| AC descreve... | E2E obrigatorio? |
|---|---|
| Funcao pura (calculo, validacao) | NAO |
| API endpoint (request/response) | NAO |
| Tela, formulario, botao, modal | SIM |
| Navegacao entre paginas | SIM |
| Feedback visual (toast, loading, erro na tela) | SIM |
| Upload de arquivo via UI | SIM |
| Responsividade mobile | SIM |

### Ferramentas

| Ferramenta | Quando usar |
|---|---|
| Laravel Dusk (PHPUnit + Chrome) | Padrao para slices Laravel com Livewire |
| Playwright (via MCP) | Alternativa ou complemento — disponivel via `mcp__plugin_playwright_playwright__*` |

### Como o functional-reviewer valida

1. Le o spec e identifica ACs visuais
2. Verifica se existe teste E2E para cada AC visual
3. Se AC visual nao tem teste E2E → finding de severidade `critical`
4. Se tem teste E2E → roda e verifica exit code

### Configuracao (quando Laravel estiver inicializado)

```bash
# Laravel Dusk (padrao)
composer require --dev laravel/dusk
php artisan dusk:install

# Ou Playwright via MCP (ja disponivel)
# Usar tools mcp__plugin_playwright_playwright__browser_navigate, browser_click, etc.
```

### Excecoes

Slices puramente backend (sem UI) nao precisam de E2E. O functional-reviewer valida isso checando se algum AC menciona termos visuais (tela, formulario, botao, pagina, modal, toast, etc.).

---

## Integracao com o Pipeline

```
/verify-slice NNN
  → mechanical-gates.sh (testes unitarios + PHPStan + Pint + audit)
  → verifier (agente, adversarial)

/review-pr NNN
  → reviewer (agente, adversarial)

/security-review NNN
  → security-scan.sh (composer audit + secrets + PHPStan)
  → security-reviewer (agente, adversarial)

/test-audit NNN
  → test-auditor (agente, adversarial) — verifica se ACs visuais tem E2E

/functional-review NNN
  → functional-reviewer (agente, adversarial)
  → Se slice tem UI: roda Playwright/Dusk e valida no browser
  → Se nao tem UI: valida via testes + leitura de codigo
```

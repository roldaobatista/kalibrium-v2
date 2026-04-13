# Plano técnico do slice 006 — Frontend base com Vite 8, Tailwind CSS 4, Livewire 4 e Alpine.js

**Gerado por:** architect sub-agent
**Status:** approved
**Spec de origem:** `specs/006/spec.md`

---

## Decisões arquiteturais

### D1: Livewire 4 via Composer, não como mock de Blade

**Opções consideradas:**
- **Opção A: adicionar `livewire/livewire` em `composer.json`** — prós: atende o `php artisan livewire:list`, cria o componente real `App\Livewire\Ping` e valida a stack declarada no slice; contras: depende da constraint disponível no Composer.
- **Opção B: simular uma página Blade estática** — prós: menos dependências; contras: não valida Livewire, não atende o AC-005 e enfraquece a base para os próximos slices com UI.

**Escolhida:** Opção A.

**Razão:** o slice existe para fechar a fundação de frontend, e o spec exige Livewire 4 de forma verificável. Sem a dependência real, o comando `livewire:list` não existe e o componente de sanidade deixa de provar a integração.

**Reversibilidade:** fácil.

**ADR:** `docs/adr/0001-stack-choice.md`.

### D2: Tailwind CSS 4 no modo CSS-first, sem voltar para `tailwind.config.js`

**Opções consideradas:**
- **Opção A: manter o padrão CSS-first em `resources/css/app.css` com `@import 'tailwindcss'` e o plugin do Vite** — prós: segue o padrão oficial do Tailwind 4, evita configuração duplicada e reduz risco de divergência; contras: difere do fluxo antigo baseado em `tailwind.config.js`.
- **Opção B: criar `tailwind.config.js` tradicional** — prós: familiar para quem veio do Tailwind 3; contras: adiciona uma camada de configuração desnecessária e pode conflitar com a stack do ADR-0001.

**Escolhida:** Opção A.

**Razão:** o objetivo é validar a base já definida pela stack, não recriá-la em um padrão legado. O arquivo CSS e o Vite devem concentrar a configuração do layout base.

**Reversibilidade:** fácil.

**ADR:** `docs/adr/0001-stack-choice.md`.

### D3: Rota `/ping` apenas fora de `production`

**Opções consideradas:**
- **Opção A: registrar `/ping` condicionalmente em `routes/web.php` quando o ambiente não for `production`** — prós: atende `AC-002` e `AC-SEC-001` ao mesmo tempo; contras: exige teste explícito para o caso de produção.
- **Opção B: expor `/ping` sempre e proteger com autenticação** — prós: acessível em qualquer ambiente; contras: cria superfície de diagnóstico em produção e desloca a decisão para E02, que não é o escopo deste slice.

**Escolhida:** Opção A.

**Razão:** o spec pede uma rota técnica de sanidade, não uma página pública. Em produção, o comportamento correto é 404.

**Reversibilidade:** fácil.

### D4: Componente `Ping` mínimo com contador reativo

**Opções consideradas:**
- **Opção A: componente Livewire `App\Livewire\Ping` com estado local simples e view dedicada** — prós: prova renderização, auto-discovery e reatividade mínima; contras: é um artefato técnico, não funcional.
- **Opção B: controller ou closure retornando HTML estático** — prós: menos arquivos; contras: não valida Livewire nem a integração pedida pelo slice.

**Escolhida:** Opção A.

**Razão:** o slice precisa deixar uma rota técnica que confirme a stack de frontend sem entrar em telas de negócio. O contador reativo é suficiente para provar o ciclo Livewire.

**Reversibilidade:** fácil.

### D5: Uma suite focada em `tests/slice-006/FrontendBaseTest.php`

**Opções consideradas:**
- **Opção A: concentrar os ACs em uma suite única por slice** — prós: leitura simples, cada AC tem evidência direta e o build/teste fica rastreável; contras: o teste faz várias checagens de runtime.
- **Opção B: espalhar os ACs em múltiplos arquivos** — prós: separação por tipo de assertiva; contras: fragmenta a leitura e dificulta rastrear o slice como unidade.

**Escolhida:** Opção A.

**Razão:** os ACs são pequenos, relacionados e todos dependem da mesma base de frontend. Uma suite única mantém o slice autocontido e fácil de validar.

**Reversibilidade:** fácil.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js` | `tests/slice-006/FrontendBaseTest.php` |
| AC-002 | `routes/web.php`, `app/Livewire/Ping.php`, `resources/views/livewire/ping.blade.php`, `resources/views/layouts/app.blade.php` | `tests/slice-006/FrontendBaseTest.php` |
| AC-003 | `vite.config.js`, `resources/css/app.css`, `resources/js/app.js` | `tests/slice-006/FrontendBaseTest.php` |
| AC-004 | `app/Livewire/Ping.php` | `tests/slice-006/FrontendBaseTest.php` |
| AC-005 | `composer.json`, `composer.lock`, `app/Livewire/Ping.php`, `resources/views/livewire/ping.blade.php` | `tests/slice-006/FrontendBaseTest.php` |
| AC-006 | `tests/slice-006/FrontendBaseTest.php` | `tests/slice-006/FrontendBaseTest.php` |
| AC-007 | `routes/web.php` | `tests/slice-006/FrontendBaseTest.php` |
| AC-008 | `tests/slice-006/FrontendBaseTest.php` | `tests/slice-006/FrontendBaseTest.php` |
| AC-009 | `app/Livewire/Ping.php` | `tests/slice-006/FrontendBaseTest.php` |
| AC-010 | `app/Livewire/Ping.php`, `routes/web.php` | `tests/slice-006/FrontendBaseTest.php` |
| AC-SEC-001 | `routes/web.php`, `resources/views/livewire/ping.blade.php` | `tests/slice-006/FrontendBaseTest.php` |

---

## Novos arquivos

- `app/Livewire/Ping.php` — componente Livewire mínimo para provar auto-discovery e reatividade.
- `resources/views/livewire/ping.blade.php` — view técnica com o texto `Livewire OK` e o contador.
- `resources/views/layouts/app.blade.php` — layout base para carregar Vite e Livewire com a pilha correta.
- `tests/slice-006/FrontendBaseTest.php` — suite de ACs do slice 006.

## Arquivos modificados

- `composer.json` — adicionar `livewire/livewire` se ainda não estiver presente.
- `composer.lock` — travar a versão resolvida do Livewire.
- `package.json` — garantir as dependências frontend exigidas pela stack do slice.
- `package-lock.json` — travar os artefatos npm resolvidos.
- `vite.config.js` — confirmar a entrada JS/CSS e o plugin necessário para Tailwind 4.
- `resources/css/app.css` — manter o fluxo CSS-first do Tailwind 4.
- `resources/js/app.js` — inicializar Alpine e qualquer bootstrap mínimo do frontend.
- `routes/web.php` — registrar `/ping` somente fora de `production`.

## Schema / migrations

Nenhuma migration neste slice. A entrega é de frontend/runtime e não persiste dados.

## APIs / contratos

### GET `/ping`

**Escopo:** rota técnica de sanidade, disponível apenas quando `APP_ENV` não for `production`.

**Resposta 200:**
```html
Livewire OK
```

**Resposta em production:** 404.

**Observação:** a rota é técnica e não representa uma API pública de produto.

## Riscos e mitigações

- **Livewire 4 pode exigir ajuste de constraint no Composer** → mitigação: usar a versão compatível com Laravel 13 que o Composer resolver e registrar qualquer ajuste mínimo no `composer.json`.
- **Tailwind 4 pode divergir do padrão antigo com `tailwind.config.js`** → mitigação: seguir o modo CSS-first e só adicionar configuração extra se o build provar que ela é necessária.
- **Alpine pode ser inicializado duas vezes se já vier embutido pela stack** → mitigação: concentrar a inicialização em um único ponto, `resources/js/app.js`, e não duplicar bootstrap.
- **A rota `/ping` pode vazar em production por erro de registro** → mitigação: condicionar a declaração da rota e cobrir com o caso 404 no teste do slice.
- **O formato do manifest do Vite pode variar entre versões** → mitigação: validar apenas as chaves que o spec exige, sem acoplar o teste ao JSON inteiro.

## Dependências de outros slices

- `slice-001` — scaffold Laravel, Vite base, Pest, estrutura `resources/` e `routes/`.
- `slice-003` — pipeline de CI já existente para validar build, teste e análise estática.

## Fora de escopo deste plano (confirmando spec)

- Qualquer tela de negócio.
- Autenticação, tenant, cliente, instrumento, padrão, OS ou certificado.
- Design system completo.
- Componentes reutilizáveis de formulário, botão, tabela, card ou modal.
- PWA, service worker e offline.
- Testes browser E2E.

# Naming Conventions — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** C.2 / G.10

---

## 1. Principios

- Nome em portugues para conceitos de produto quando aparecer para usuario.
- Nome em ingles para classes, metodos e artefatos de codigo, seguindo convencoes Laravel.
- Nome de dominio deve ser consistente entre PRD, stories, routes, tests e docs.
- Abreviacao so quando for consagrada: `OS`, `PDF`, `RBC`, `LGPD`.
- Nao misturar singular/plural no mesmo tipo de artefato.

---

## 2. PHP e Laravel

| Artefato | Padrao | Exemplo |
|---|---|---|
| Model | singular PascalCase | `Instrument`, `ServiceOrder` |
| Controller | PascalCase + `Controller` | `InstrumentController` |
| Livewire page | PascalCase + `Page` | `InstrumentIndexPage` |
| Form object | PascalCase + `Form` | `InstrumentForm` |
| Policy | Model + `Policy` | `InstrumentPolicy` |
| Event | passado ou fato de dominio | `InstrumentCreated` |
| Listener | verbo + alvo | `SendCertificateEmail` |
| Job | verbo imperativo | `GenerateCertificatePdf` |
| Notification | alvo + evento | `CertificateIssuedNotification` |
| Command | verbo + contexto | `DbCheckCommand` |

Namespaces:

```text
App\Domain\<Contexto>
App\Infrastructure\<Adapter>
App\Livewire\<Modulo>
App\Http\Controllers
```

---

## 3. Banco de dados

| Artefato | Padrao | Exemplo |
|---|---|---|
| Tabela | snake_case plural | `instruments`, `service_orders` |
| Coluna | snake_case | `serial_number` |
| Foreign key | singular + `_id` | `customer_id` |
| Pivot | singular alfabetico | `instrument_service_order` |
| Migration | verbo Laravel | `create_instruments_table` |
| Enum textual | snake_case | `waiting_review` |

Regras:
- UUIDs usam coluna `id` quando forem chave primaria.
- Timestamps Laravel (`created_at`, `updated_at`) sao mantidos.
- Auditoria usa nomes explicitos: `approved_at`, `approved_by`, `revoked_reason`.

---

## 4. Rotas e URLs

| Tipo | Padrao | Exemplo |
|---|---|---|
| Recurso | kebab-case plural | `/instrumentos` |
| Detalhe | id/codigo na rota | `/instrumentos/{instrument}` |
| Acao especial | verbo curto | `/certificados/{certificate}/revogar` |
| Portal publico | prefixo `portal` | `/portal/certificados` |
| Admin global | prefixo `admin` | `/admin/tenants` |

Nomes de rota:

```text
instruments.index
instruments.create
instruments.edit
certificates.revoke
portal.certificates.index
```

---

## 5. Views e componentes

| Artefato | Padrao | Exemplo |
|---|---|---|
| Blade view | kebab-case | `index-page.blade.php` |
| Blade component | kebab-case | `status-badge.blade.php` |
| Livewire view | espelha classe | `instrumentos/index-page.blade.php` |
| CSS utility custom | kebab-case | `app-shell` |
| Alpine data | camelCase | `dropdownOpen` |

Classes CSS proprias devem ser raras. Preferir Tailwind utility-first; quando classe propria for necessaria, prefixar por contexto:

```text
app-shell
print-document
certificate-preview
```

---

## 6. Testes

| Tipo | Padrao | Exemplo |
|---|---|---|
| Feature test | comportamento esperado | `HealthCheckTest.php` |
| Unit test | classe/unidade | `InstrumentNumberTest.php` |
| Pest describe | frase de comportamento | `it('shows health status')` |
| AC script | `tests/slice-NNN/ac-tests.sh` | `tests/slice-005/ac-tests.sh` |

Testes devem citar AC quando vierem de slice:

```php
it('AC-001 returns service status', function () {
    // ...
});
```

---

## 7. Commits

Padroes principais:

```text
feat(slice-NNN): descricao
fix(slice-NNN): descricao
test(slice-NNN): descricao
docs(design): descricao
docs(architecture): descricao
chore(harness): descricao
```

Commits de harness nao devem misturar produto e governanca.

---

## 8. Checklist

| Pergunta | Obrigatorio |
|---|---|
| Nome segue convencao Laravel? | Sim |
| Conceito de dominio usa o mesmo termo em docs e codigo? | Sim |
| Rota publica esta em portugues compreensivel? | Sim |
| Teste cita AC quando aplicavel? | Sim |
| Commit tem escopo claro? | Sim |

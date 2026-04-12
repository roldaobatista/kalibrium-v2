# UI Testing Strategy — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Documento:** C.8 / G.14

---

## 1. Decisao

Testes de UI seguem a piramide: Pest unit/feature primeiro, Livewire component tests para comportamento de tela, Pest Browser como browser test padrao do stack Laravel, e Playwright apenas como fallback/complemento quando houver necessidade especifica. Acessibilidade entra como gate de UI quando houver tela implementada.

---

## 2. Piramide

| Camada | Ferramenta | Uso |
|---|---|---|
| Unit | Pest | formatadores, policies, services |
| Feature | Pest/Laravel | rotas, autorizacao, persistencia |
| Component | Livewire test helpers | filtros, forms, actions |
| Browser | Pest Browser | fluxo completo e regressao visual leve |
| Accessibility | axe-core via Pest Browser ou Playwright complementar | regras WCAG em telas criticas |

---

## 3. Quando usar browser real

Usar Pest Browser para:
- login e navegacao principal;
- criacao/edicao de entidade com formulario complexo;
- fluxo offline/PWA;
- upload de arquivo;
- emissao/preview de PDF;
- regressao de layout em tela critica.

Usar Playwright apenas como fallback/complemento quando Pest Browser nao cobrir o caso, ou para auditoria visual/acessibilidade que exigir ferramenta externa.

Nao usar browser real para:
- regra simples de service;
- validacao que Livewire cobre;
- caminho de erro puramente server-side;
- teste de cada campo isolado.

---

## 4. Estrutura sugerida

```text
tests/Feature/
tests/Unit/
tests/Browser/
tests/Livewire/
```

Nomes:

```text
InstrumentosIndexPageTest.php
CreateServiceOrderFlowTest.php
CertificatePreviewBrowserTest.php
certificate-preview.spec.ts  # apenas quando Playwright for usado como complemento
```

---

## 5. Dados de teste

- Factories para entidades persistidas.
- Seeders apenas para cenarios compartilhados.
- Dados de metrologia devem usar exemplos plausiveis.
- Teste nao deve depender de dado real de cliente.
- Fixture de PDF deve comparar contrato/estrutura, nao bytes exatos quando a engine variar.

---

## 6. Acessibilidade

Telas criticas devem validar:
- foco visivel;
- labels em campos;
- contraste minimo;
- navegacao por teclado;
- landmarks basicos;
- live regions para feedback assincrono.

---

## 7. Checklist

| Pergunta | Obrigatorio |
|---|---|
| AC de tela tem teste automatizado? | Sim |
| Comportamento Livewire foi testado sem browser quando possivel? | Sim |
| Fluxo critico tem Pest Browser, ou Playwright quando houver justificativa? | Sim |
| Tela critica tem check de acessibilidade? | Sim |
| Dados de teste sao isolados? | Sim |
| PDF/teste visual evita comparacao fragil? | Sim |

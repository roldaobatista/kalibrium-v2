# UI Testing Strategy — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** C.8 / G.14

---

## 1. Decisao

Testes de UI seguem a piramide: Pest unit/feature primeiro, Livewire component tests para comportamento de tela, Playwright apenas para fluxos de usuario que precisam de browser real. Acessibilidade entra como gate de UI quando houver tela implementada.

---

## 2. Piramide

| Camada | Ferramenta | Uso |
|---|---|---|
| Unit | Pest | formatadores, policies, services |
| Feature | Pest/Laravel | rotas, autorizacao, persistencia |
| Component | Livewire test helpers | filtros, forms, actions |
| Browser | Playwright | fluxo completo e regressao visual leve |
| Accessibility | axe-core/Playwright | regras WCAG em telas criticas |

---

## 3. Quando usar Playwright

Usar Playwright para:
- login e navegacao principal;
- criacao/edicao de entidade com formulario complexo;
- fluxo offline/PWA;
- upload de arquivo;
- emissao/preview de PDF;
- regressao de layout em tela critica.

Nao usar Playwright para:
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
InstrumentIndexPageTest.php
CreateServiceOrderFlowTest.php
certificate-preview.spec.ts
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
| Fluxo critico tem Playwright? | Sim |
| Tela critica tem check de acessibilidade? | Sim |
| Dados de teste sao isolados? | Sim |
| PDF/teste visual evita comparacao fragil? | Sim |

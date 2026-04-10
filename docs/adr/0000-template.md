# ADR-0000 — Template

**Status:** proposed | accepted | superseded by NNNN | deprecated
**Data:** YYYY-MM-DD
**Autor:** nome humano (+ Co-Authored-By Claude se aplicável)

---

## Contexto

Qual é o problema? Qual o estado atual? Que forças/restrições levaram a precisar decidir agora?

Evitar: lista de requisitos sem priorização. Ser específico sobre **qual decisão** precisa ser tomada.

## Opções consideradas

Pelo menos **2 opções**. ADR com opção única é rejeitado em review.

### Opção A: <nome>
- Descrição: ...
- Prós:
  - ...
- Contras:
  - ...
- Custo de reverter: baixo / médio / alto

### Opção B: <nome>
- Descrição: ...
- Prós:
  - ...
- Contras:
  - ...
- Custo de reverter: baixo / médio / alto

### Opção C: <nome opcional>
...

## Decisão

**Opção escolhida:** A | B | C

**Razão:** explicar **por que** esta e não as outras. Amarrar à constitution ou a outro ADR quando relevante.

**Reversibilidade:** fácil / média / difícil

## Consequências

### Positivas
- ...

### Negativas
- ...

### Riscos
- ...

### Impacto em outros artefatos
- Hooks afetados: ...
- Sub-agents afetados: ...
- ADRs relacionados: ...

## Referências

- Slice que motivou: `specs/NNN/`
- Discussão: link ou commit hash
- Documentação externa consultada (URLs verificadas na data desta ADR)

---

## Checklist de aceitação (revisor)

- [ ] Pelo menos 2 opções reais consideradas
- [ ] Decisão justificada sem "porque sim"
- [ ] Reversibilidade declarada
- [ ] Consequências negativas listadas
- [ ] Não contradiz ADR anterior (ou declara `superseded by`)
- [ ] Impacto em hooks/agents/constitution endereçado

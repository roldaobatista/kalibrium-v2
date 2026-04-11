# Matriz de rastreabilidade — template

> **Status:** template ativo. Item T2.13 da Trilha #2. Consome-se diretamente por qualquer arquivo de policy por domínio (T2.10) que precise amarrar requisito legal a implementação e teste. Linha única por requisito.

## Estrutura da linha

```
| norma | seção | data | requisito | teste golden | slice | consultor responsável | data de revalidação | módulo proibido para IA? |
```

## Descrição das colunas

| Coluna | Tipo | Descrição |
|---|---|---|
| norma | string | Nome oficial abreviado da norma (exemplos: "LGPD", "ISO/IEC 17025:2017", "Lei 8.078/1990", "ABNT NBR ISO/IEC 17025", "Portaria MTP 671/2021"). |
| seção | string | Artigo, item ou seção específica que motiva o requisito. |
| data | string (AAAA-MM-DD) | Data de publicação ou última revisão da seção referenciada. |
| requisito | string | Uma frase objetiva com o que a norma exige. Sem jargão jurídico. |
| teste golden | path relativo | Caminho para o teste que valida o requisito (por exemplo `tests/golden/metrology/gum-cases.csv`). Vazio quando o teste ainda não existe, mas linha ainda precisa ser criada. |
| slice | `slice-NNN` | Slice que implementa o requisito. Vazio quando ainda não existe, com criação prevista no `slice-registry.md`. |
| consultor responsável | string | Qual consultor assina a validação externa (metrologia, fiscal, DPO, advogado LGPD). |
| data de revalidação | string (AAAA-MM-DD) | Próxima data em que o requisito precisa ser reverificado contra a fonte oficial. Replicada no `revalidation-calendar.md`. |
| módulo proibido para IA? | `sim` / `não` | Se `sim`, a implementação precisa passar por integrador humano ou consultor externo (consulte `ia-no-go.md`, item T2.15). |

## Regras de uso

1. **Nunca apagar linha.** Só marcar como `substituída por <nova linha>`.
2. **Vazio é aceitável apenas para `teste golden` e `slice`** enquanto o slice ainda não foi criado. Nenhum outro campo pode estar vazio.
3. **Linha com `módulo proibido para IA? = sim` bloqueia implementação** até existir avaliação formal (em `ia-no-go.md`) autorizando caminho alternativo.
4. **Uma linha = um requisito.** Requisito composto vira linhas separadas.

## Exemplo de uso (linha de amostra preenchida pelo consultor de metrologia no item M3)

| norma | seção | data | requisito | teste golden | slice | consultor responsável | data de revalidação | módulo proibido para IA? |
|---|---|---|---|---|---|---|---|---|
| GUM/JCGM 100:2008 | §5 | 2008-09-01 | Cálculo de incerteza expandida com fator de abrangência declarado | tests/golden/metrology/gum-cases.csv | — | consultor metrologia | 2027-04-10 | não |

## Onde esta matriz é usada

- Cada arquivo de `docs/compliance/<dominio>-policy.md` (T2.10) tem uma seção "Matriz de rastreabilidade" preenchida com linhas concretas desta estrutura.
- O hook `pre-push-gate.sh` pode ser estendido (no Bloco 3 item 3.5) para validar que cada slice citado em uma linha existe em `slice-registry.md`.
- O relatório mensal de law-watch (T2.12) cruza as linhas com vencimento em menos de 30 dias.

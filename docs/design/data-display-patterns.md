# Data Display Patterns — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Documento:** B.8 / G.8
> **Dependencias:** `docs/design/style-guide.md` v1.0.0, `docs/design/component-patterns.md` v1.0.0, `docs/design/interaction-patterns.md` v1.0.0
> **Escopo:** regras visuais para exibir dados tecnicos, financeiros, cadastrais e operacionais na interface.

---

## 1. Principios

Dados no Kalibrium precisam ser claros para leitura rapida e seguros para auditoria. A interface nunca deve "embelezar" um dado a ponto de esconder precisao, unidade, origem ou status.

### 1.1. Regras gerais

- Exibir unidade sempre que o numero representar uma medida.
- Separar valor exibido de valor armazenado: a UI formata, o banco guarda o valor canonico.
- Nunca arredondar dado de medicao sem regra explicita do dominio.
- Preferir alinhamento a direita para numeros em tabelas.
- Preferir alinhamento a esquerda para codigos, nomes, descricoes e status.
- Mostrar valores ausentes de forma consistente, sem confundir "zero" com "nao informado".
- Em contexto regulatorio, mostrar o dado bruto quando a transformacao puder alterar interpretacao.

### 1.2. Ordem de leitura

| Tipo de dado | Prioridade visual | Exemplo |
|---|---|---|
| Identificador operacional | Alta | `OS-2026-000123` |
| Status | Alta | Badge `Em calibracao` |
| Valor tecnico | Alta | `23,500 °C` |
| Data critica | Alta | `12/04/2026 14:30` |
| Responsavel | Media | `Juliana Alves` |
| Observacao | Baixa | texto truncado com tooltip |
| Metadado de auditoria | Baixa, mas sempre acessivel | `Alterado em 12/04/2026 14:31` |

---

## 2. Datas e horarios

### 2.1. Formato padrao

| Contexto | Formato | Exemplo | Regra |
|---|---|---|---|
| Data comum | `dd/mm/aaaa` | `12/04/2026` | Listas, filtros e formularios |
| Data + hora | `dd/mm/aaaa HH:mm` | `12/04/2026 14:30` | Eventos, auditoria, agendamentos |
| Hora isolada | `HH:mm` | `14:30` | Timeline do mesmo dia |
| Mes/ano | `mmm/aaaa` | `abr/2026` | Competencia fiscal ou relatórios |
| ISO tecnico | `aaaa-mm-dd` | `2026-04-12` | Exportacoes, logs, integracoes |

### 2.2. Timezone

- Interface do usuario: horario local do tenant.
- Logs tecnicos e auditoria: armazenar em UTC, exibir com timezone local quando mostrado ao usuario.
- Nunca exibir horario sem contexto quando houver impacto legal ou fiscal.

**Padrao de tooltip:**

```text
12/04/2026 14:30
tooltip: 2026-04-12T18:30:00Z · America/Cuiaba
```

### 2.3. Datas relativas

Datas relativas so podem aparecer como complemento, nunca como unico valor.

| Permitido | Proibido |
|---|---|
| `12/04/2026 14:30 · ha 5 min` | `ha 5 min` |
| `Vence em 3 dias · 15/04/2026` | `vence em breve` |
| `Atrasado ha 2 dias · venceu em 10/04/2026` | `atrasado` sem data |

---

## 3. Numeros

### 3.1. Separadores

| Tipo | Formato | Exemplo |
|---|---|---|
| Decimal | virgula | `12,45` |
| Milhar | ponto | `1.234,56` |
| Percentual | espaco antes de `%` | `12,5 %` |
| Inteiro | ponto de milhar acima de 999 | `1.250` |
| Valor negativo | sinal antes do numero | `-0,12` |

### 3.2. Casas decimais

| Dado | Casas padrao | Exemplo |
|---|---:|---|
| Temperatura ambiente | 1 | `23,5 °C` |
| Temperatura de medicao | Conforme instrumento | `23,500 °C` |
| Umidade relativa | 1 | `55,2 %UR` |
| Pressao | 2 | `101,32 kPa` |
| Massa | Conforme certificado | `1,0000 kg` |
| Incerteza | Conforme metodo | `0,012 °C` |
| Resultado financeiro | 2 | `R$ 1.234,56` |

**Regra:** casas decimais de medicao devem vir do metodo, instrumento ou certificado. A UI nao decide sozinha.

---

## 4. Moeda e valores financeiros

### 4.1. BRL

Padrao visual:

```text
R$ 1.234,56
```

Regras:
- Sempre usar `R$` com espaco.
- Valores financeiros em tabelas alinhados a direita.
- Valores totais em negrito.
- Valores negativos em `danger-700` e com sinal: `-R$ 123,45`.
- Descontos percentuais: `10 %` e valor monetario em coluna separada quando possivel.

### 4.2. Valores vazios

| Situacao | Exibicao |
|---|---|
| Valor ainda nao calculado | `A calcular` |
| Valor nao aplicavel | `Nao se aplica` |
| Valor zero real | `R$ 0,00` |
| Valor removido por permissao | `Restrito` |

---

## 5. Documentos e contatos

### 5.1. CPF e CNPJ

| Dado | Formato | Exemplo |
|---|---|---|
| CPF | `000.000.000-00` | `123.456.789-09` |
| CNPJ | `00.000.000/0000-00` | `12.345.678/0001-90` |

Regras:
- Nunca mascarar CNPJ de empresa em telas administrativas.
- CPF pode ser mascarado por permissao: `***.456.789-**`.
- Em portal do cliente, mostrar apenas documentos vinculados ao proprio cliente.

### 5.2. Telefone e e-mail

| Dado | Formato | Exemplo |
|---|---|---|
| Celular BR | `(65) 99999-1234` | `(65) 99999-1234` |
| Telefone fixo BR | `(65) 3333-1234` | `(65) 3333-1234` |
| E-mail | lowercase visual | `financeiro@cliente.com.br` |

E-mail deve quebrar linha em telas estreitas usando `break-all` apenas quando necessario.

---

## 6. Identificadores

### 6.1. Codigos operacionais

| Entidade | Formato | Exemplo |
|---|---|---|
| Ordem de servico | `OS-AAAA-000000` | `OS-2026-000123` |
| Certificado | `CERT-AAAA-000000` | `CERT-2026-000045` |
| Pedido | `PED-AAAA-000000` | `PED-2026-000078` |
| Instrumento | prefixo curto + sequencial | `INS-000321` |
| Protocolo de suporte | `OUV-AAAA-000000` | `OUV-2026-000001` |

Regras:
- Codigos ficam em fonte monoespacada em tabelas e detalhes.
- Codigos clicaveis usam link azul `primary-600`.
- Copiar codigo deve ter feedback via Toast (#26).

### 6.2. IDs internos

IDs internos de banco nao aparecem para o usuario final. Se necessario para suporte, exibir em area tecnica colapsada:

```text
Detalhes tecnicos
ID interno: 8f65b0c4-...
```

---

## 7. Status badges

Usar Badge/Tag (#10) de `component-patterns.md`.

### 7.1. Ordem de servico

| Status | Label | Cor | Uso |
|---|---|---|---|
| `draft` | Rascunho | `neutral` | Criada mas incompleta |
| `scheduled` | Agendada | `info` | Data definida |
| `in_progress` | Em execucao | `primary` | Tecnico trabalhando |
| `on_hold` | Pausada | `warning` | Aguardando insumo, cliente ou decisao |
| `waiting_review` | Aguardando revisao | `warning` | Depende do gerente |
| `returned` | Devolvida para retrabalho | `danger` | Revisao pediu correcao tecnica |
| `approved` | Aprovada | `success` | Pode gerar certificado |
| `completed` | Concluida | `success` | Entrega operacional encerrada |
| `cancelled` | Cancelada | `danger` | Encerrada sem entrega |

### 7.2. Certificado

| Status | Label | Cor | Regra |
|---|---|---|---|
| `draft` | Rascunho | `neutral` | Nunca enviar ao cliente |
| `pending_signature` | Aguardando assinatura | `warning` | Bloqueia envio |
| `issued` | Emitido | `success` | Documento oficial |
| `sent` | Enviado | `info` | Cliente notificado |
| `revoked` | Revogado | `danger` | Exigir motivo visivel |
| `expired` | Vencido | `neutral` | Sem destaque vermelho se nao houver risco imediato |

### 7.3. Pagamentos

| Status | Label | Cor |
|---|---|---|
| `open` | Em aberto | `warning` |
| `paid` | Pago | `success` |
| `overdue` | Vencido | `danger` |
| `cancelled` | Cancelado | `neutral` |
| `refunded` | Estornado | `info` |

### 7.4. Documentos fiscais

| Status | Label | Cor | Regra |
|---|---|---|---|
| `draft` | Rascunho | `neutral` | Ainda nao transmitida |
| `transmitting` | Transmitindo | `info` | Aguardando prefeitura |
| `authorized` | Autorizada | `success` | Documento fiscal valido |
| `rejected` | Rejeitada | `danger` | Exige correcao/reprocesso |
| `cancelled` | Cancelada | `neutral` | Exibir protocolo quando houver |

---

## 8. Valores tecnicos de metrologia

### 8.1. Unidade de medida

| Tipo | Exemplo | Regra |
|---|---|---|
| Temperatura | `23,500 °C` | espaco antes de `°C` |
| Umidade | `55,2 %UR` | sem converter para decimal |
| Pressao | `101,32 kPa` | unidade apos valor |
| Massa | `1,0000 kg` | casas conforme metodo |
| Comprimento | `25,000 mm` | unidade explicita |
| Eletrica | `10,000 V` | unidade SI |

### 8.2. Incerteza

Formato preferencial:

```text
23,500 °C ± 0,012 °C
k = 2 · 95 %
```

Regras:
- Mostrar `k` e nivel de confianca quando o valor for parte do certificado.
- Nao esconder incerteza em tooltip em tela de revisao tecnica.
- Em tabela resumida, pode quebrar em duas linhas:

```text
23,500 °C
± 0,012 °C
```

### 8.3. Notacao cientifica

Usar apenas quando o valor fica mais legivel ou quando o metodo exige.

| Valor armazenado | Exibicao preferida |
|---|---|
| `0.0000012` | `1,2 × 10^-6` |
| `1200000` | `1,2 × 10^6` |
| `0.012` | `0,012` |

Em certificados, a notacao deve seguir o template aprovado do laboratorio.

---

## 9. Valores ausentes, nulos e restritos

| Situacao | Exibicao | Cor |
|---|---|---|
| Campo opcional vazio | `Nao informado` | `neutral-500` |
| Campo obrigatorio ausente | `Pendente` | `warning` |
| Nao se aplica | `Nao se aplica` | `neutral-500` |
| Sem permissao | `Restrito` | `neutral-500` |
| Erro ao carregar | `Nao foi possivel carregar` | `danger` |
| Valor zero real | `0`, `0,00` ou `R$ 0,00` | normal |

Nunca usar apenas `-` para todos os casos. O hifen pode aparecer em tabelas muito densas, mas deve ter `aria-label` explicando o motivo.

---

## 10. Truncamento e textos longos

### 10.1. Tabelas

| Campo | Limite | Comportamento |
|---|---:|---|
| Nome de cliente | 40 caracteres | truncar com tooltip |
| Nome de instrumento | 48 caracteres | truncar com tooltip |
| Observacao | 80 caracteres | truncar e abrir em modal/detalhe |
| E-mail | sem limite fixo | quebrar se necessario |
| Codigo | nunca truncar | reduzir coluna ao minimo viavel |

### 10.2. Tooltips

Tooltip de truncamento deve:
- aparecer em hover e focus;
- conter o texto completo;
- nao ser a unica forma de acessar informacao critica em mobile.

Em mobile, textos truncados criticos devem abrir bottom sheet ou area expandida.

---

## 11. Tabelas densas

### 11.1. Alinhamento

| Tipo de coluna | Alinhamento |
|---|---|
| Codigo | esquerda, monoespacado |
| Nome / descricao | esquerda |
| Data | esquerda ou centro |
| Numero / moeda / medida | direita |
| Status | centro ou esquerda |
| Acoes | direita |

### 11.2. Ordenacao

Ordenar pelo valor canonico, nao pelo texto formatado.

Exemplo:
- Exibicao: `R$ 1.234,56`
- Ordenacao: `1234.56`

### 11.3. Exportacao

Exportacoes CSV/XLSX devem incluir:
- valor canonico em coluna propria quando necessario;
- unidade em cabecalho ou coluna propria;
- timezone documentado para datas;
- status com label humano, nao enum cru.

---

## Apendice A — Checklist por tela

| Pergunta | Obrigatorio |
|---|---|
| Todo numero tecnico tem unidade? | Sim |
| A quantidade de casas decimais vem do metodo/instrumento? | Sim |
| Datas criticas mostram data absoluta? | Sim |
| Status usa badge padronizado? | Sim |
| Valores ausentes diferenciam zero, vazio e restrito? | Sim |
| Campos truncados tem acesso ao texto completo? | Sim |
| Tabelas ordenam pelo valor canonico? | Sim |
| Exportacao preserva unidade e timezone? | Sim |

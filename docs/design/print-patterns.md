# Print and PDF Patterns — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** B.9 / G.15
> **Dependencias:** `docs/design/style-guide.md` v1.0.0, `docs/design/data-display-patterns.md` v1.0.0
> **Escopo:** regras visuais e comportamentais para documentos imprimiveis, PDFs e pre-visualizacao de impressao.

---

## 1. Principios

Documentos impressos e PDFs do Kalibrium tem valor operacional, fiscal, comercial e regulatorio. A versao impressa precisa ser legivel sem depender da interface web e precisa preservar identidade, rastreabilidade e contexto.

### 1.1. Regras gerais

- Todo documento imprimivel tem titulo, identificador, data de emissao e pagina.
- Documento oficial nunca depende de cor para transmitir status.
- Cabecalho e rodape devem funcionar em preto e branco.
- PDFs oficiais devem ser gerados a partir de templates versionados.
- Rascunhos precisam ter marca visual clara.
- A pre-visualizacao na UI deve usar o mesmo conteudo logico do PDF final.
- A engine de PDF sera decidida antes de E06; este documento define o contrato visual e funcional.

---

## 2. Tipos de documento

| Tipo | Uso | Status | Criticidade |
|---|---|---|---|
| Certificado de calibracao | Entrega oficial ao cliente | E06 | Critica |
| Ordem de servico impressa | Apoio de campo/bancada | E04/E05 | Media |
| Relatorio gerencial | Gestao interna e cliente | E09+ | Media |
| Recibo / comprovante | Financeiro | E08+ | Media |
| Exportacao tabular PDF | Listas e consultas | Geral | Baixa |

---

## 3. Pagina e medidas

### 3.1. Papel

| Documento | Formato | Orientacao | Margens |
|---|---|---|---|
| Certificado | A4 | Retrato | 18mm superior, 15mm laterais, 18mm inferior |
| OS impressa | A4 | Retrato | 15mm |
| Relatorio gerencial | A4 | Retrato ou paisagem | 15mm |
| Tabela larga | A4 | Paisagem | 12mm |

### 3.2. Grid

- Unidade base: `4mm`.
- Conteudo principal usa largura total menos margens.
- Tabelas devem ter altura de linha minima de `7mm`.
- Rodape reservado: `14mm` minimos.

---

## 4. Tipografia para impressao

| Elemento | Fonte | Tamanho | Peso |
|---|---|---:|---|
| Titulo do documento | sans-serif | 16pt | 700 |
| Secao | sans-serif | 11pt | 700 |
| Corpo | sans-serif | 9pt | 400 |
| Tabela | sans-serif | 8pt | 400 |
| Rodape | sans-serif | 7pt | 400 |
| Codigo / identificador | monospace | 8pt | 400 |

Regras:
- Texto de corpo nunca abaixo de `8pt`.
- Dados tecnicos criticos nunca abaixo de `8pt`.
- Certificados podem usar fonte serif apenas se o template oficial do laboratorio exigir.
- Links em PDF devem mostrar URL completa quando o documento for impresso.

---

## 5. Cabecalho

### 5.1. Cabecalho padrao

```text
┌──────────────────────────────────────────────────────────────┐
│ [Logo tenant]  Nome do laboratorio                           │
│                CNPJ 00.000.000/0000-00 · Cidade/UF           │
│                Documento: CERT-2026-000045                   │
└──────────────────────────────────────────────────────────────┘
```

Regras:
- Logo maxima: `34mm × 14mm`.
- Se nao houver logo, usar nome do laboratorio.
- Documento oficial deve mostrar CNPJ e cidade/UF do tenant.
- Cabecalho nao deve ocupar mais de 18% da primeira pagina.

### 5.2. Paginas seguintes

Cabecalho reduzido:

```text
CERT-2026-000045 · Cliente ABC Ltda · Pagina 2 de 4
```

---

## 6. Rodape

Rodape padrao:

```text
Emitido por Kalibrium em 12/04/2026 14:30 · America/Cuiaba
Pagina 1 de 4 · Verificacao: https://kalibrium.com.br/v/CERT-2026-000045
```

Regras:
- Toda pagina deve ter numero.
- Documento oficial deve ter URL ou codigo de verificacao quando aplicavel.
- Rascunho deve exibir "RASCUNHO — sem valor oficial" no rodape e marca d'agua.
- Rodape nao pode conter informacao que seja a unica prova de conformidade.

---

## 7. Certificado de calibracao

### 7.1. Estrutura minima

| Secao | Conteudo |
|---|---|
| Identificacao | numero do certificado, revisao, data de emissao |
| Cliente | razao social, CNPJ, endereco quando aplicavel |
| Instrumento | identificacao, fabricante, modelo, numero de serie, faixa |
| Procedimento | metodo, local, condicoes ambientais |
| Padroes utilizados | padrao, certificado, validade, rastreabilidade |
| Resultados | tabela de pontos, erro, correcao, incerteza |
| Declaracao | conformidade, criterio de aceitacao, observacoes |
| Assinatura | responsavel tecnico, cargo, registro quando aplicavel |
| Verificacao | QR code ou URL verificavel |

### 7.2. Tabela de resultados

```text
┌────────────┬────────────┬────────────┬────────────┬────────────┐
│ Ponto      │ Leitura    │ Erro       │ Incerteza  │ Situacao   │
├────────────┼────────────┼────────────┼────────────┼────────────┤
│ 0,000 °C   │ 0,002 °C   │ 0,002 °C   │ 0,012 °C   │ Conforme   │
│ 50,000 °C  │ 49,998 °C  │ -0,002 °C  │ 0,015 °C   │ Conforme   │
└────────────┴────────────┴────────────┴────────────┴────────────┘
```

Regras:
- Unidades aparecem no cabecalho quando todos os valores da coluna compartilham unidade.
- Se a unidade variar por linha, aparece em cada celula.
- Incerteza deve indicar `k` e nivel de confianca na secao de notas ou coluna dedicada.
- Nunca quebrar uma linha de resultado entre paginas; repetir cabecalho da tabela na pagina seguinte.

### 7.3. Rascunho vs emitido

| Status | Marca visual |
|---|---|
| Rascunho | marca d'agua diagonal `RASCUNHO` + rodape sem valor oficial |
| Aguardando assinatura | banner superior `Aguardando assinatura` |
| Emitido | sem marca d'agua, com verificacao publica |
| Revogado | marca d'agua `REVOGADO` + motivo e data da revogacao |

---

## 8. Ordem de servico impressa

### 8.1. Estrutura

```text
OS-2026-000123
Cliente: ABC Industria Ltda
Tecnico: Juliana Alves
Data agendada: 12/04/2026 08:00

Instrumentos:
1. Termometro digital TD-100 · Serie 12345
2. Balanca B-200 · Serie 99887

Checklist:
[ ] Conferir identificacao
[ ] Registrar condicoes ambientais
[ ] Anexar fotos quando necessario
```

Regras:
- Deve funcionar com preenchimento manual em campo.
- Campos de assinatura precisam ter linha e nome legivel.
- QR code para abrir OS no sistema quando online.
- Versao impressa deve deixar claro se os dados estao desatualizados.

---

## 9. Relatorios gerenciais

### 9.1. Layout

- Capa opcional apenas para relatorios com mais de 5 paginas.
- Sumario executivo na primeira pagina.
- KPIs em tabela, nao apenas cards coloridos.
- Graficos devem ter legenda e valores numericos.
- Tabelas longas repetem cabecalho em cada pagina.

### 9.2. Graficos

Regras:
- Nunca depender apenas de cor; usar legenda textual.
- Evitar gradientes e fundos escuros em impressao.
- Mostrar periodo analisado no titulo do grafico.
- Se o grafico for exportado como imagem, garantir resolucao minima de 2x.

---

## 10. CSS de impressao

### 10.1. Regras base

```css
@media print {
  @page {
    size: A4;
    margin: 18mm 15mm;
  }

  body {
    background: white;
    color: #111827;
    font-size: 9pt;
  }

  .no-print {
    display: none !important;
  }

  .print-break-before {
    break-before: page;
  }

  .print-avoid-break {
    break-inside: avoid;
  }
}
```

### 10.2. Proibido em impressao

- Sombras decorativas.
- Background escuro como requisito de leitura.
- Botoes e controles interativos.
- Sidebar, header da aplicacao e navegacao.
- Toasts, modais e overlays.
- Texto branco sobre fundo colorido sem fallback.

---

## 11. Pre-visualizacao na UI

### 11.1. Padrao de tela

```text
┌──────────────────────────────────────────────────────────────┐
│ Certificado CERT-2026-000045                  [Baixar PDF]   │
│ Status: Rascunho                              [Imprimir]     │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │                                                        │  │
│  │               Preview A4 do documento                  │  │
│  │                                                        │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

Regras:
- Preview pode ser reduzido visualmente, mas nao pode reflowar conteudo de forma diferente do PDF.
- Acoes destrutivas (revogar, substituir PDF) nao ficam no mesmo grupo visual de "Baixar" e "Imprimir".
- Se o PDF ainda estiver sendo gerado, usar Loading State de `interaction-patterns.md`.

---

## 12. Arquivo e nome

| Documento | Nome de arquivo |
|---|---|
| Certificado | `CERT-2026-000045-cliente-abc.pdf` |
| OS | `OS-2026-000123-cliente-abc.pdf` |
| Relatorio | `relatorio-calibracoes-2026-04.pdf` |

Regras:
- Lowercase, sem acentos, sem espacos.
- Identificador primeiro.
- Nome do cliente sanitizado.
- Nunca incluir CPF/CNPJ no nome do arquivo.

---

## 13. Acessibilidade e auditoria

- PDF deve ter titulo e idioma (`pt-BR`) quando a engine suportar.
- Ordem de leitura deve seguir a ordem visual.
- Tabelas devem ter cabecalhos semanticamente marcados quando possivel.
- Texto importante nao deve ser imagem.
- QR code deve ter URL textual alternativa.
- Todo PDF oficial deve registrar quem gerou, quando gerou e qual template foi usado.

---

## Apendice A — Checklist antes de aprovar template PDF

| Pergunta | Obrigatorio |
|---|---|
| Documento tem titulo, identificador, data e pagina? | Sim |
| Cabecalho funciona sem logo? | Sim |
| Rodape tem verificacao quando aplicavel? | Sim |
| Rascunho e emitido sao visualmente diferentes? | Sim |
| Tabelas repetem cabecalho ao quebrar pagina? | Sim |
| Dados tecnicos preservam unidade e casas decimais? | Sim |
| PDF impresso em preto e branco continua legivel? | Sim |
| Nome do arquivo nao contem dado sensivel indevido? | Sim |
| Template versionado foi registrado? | Sim |

# Wireframe — Formulario de Procedimento de Calibracao (Versionado)

> **Telas:** Novo Procedimento / Detalhe do Procedimento / Nova Versao
> **URLs:** `/procedimentos/novo` | `/procedimentos/{procedimento}`
> **Epico:** E03 — Cadastro Core
> **Story:** E03-S06
> **Persona primaria:** Marcelo (gerente)
> **Role minima:** `gerente` (criacao/edicao); `tecnico` (leitura)
> **SCR-IDs:** SCR-E03-012
> **Wireframe status:** draft

---

## Layout — Novo Procedimento (`/procedimentos/novo`)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar...                               🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │  Home > Laboratorio > Procedimentos > Novo Procedimento        │
│              │                                                               │
│ ▼ Laborat.  │  Novo Procedimento de Calibracao                              │
│   ◼ Proced. │                                                               │
│              │  ┌─ Identificacao ──────────────────────────────────────────┐ │
│              │  │                                                          │ │
│              │  │  Codigo interno *                                        │ │
│              │  │  [PROC-____]                                             │ │
│              │  │  Ex: PROC-D-01 (D=dimensional, P=pressao, M=massa, T=temp)│
│              │  │                                                          │ │
│              │  │  Nome descritivo *                                       │ │
│              │  │  [____________________________________________]          │ │
│              │  │  ex: Calibracao de paquimetro escala principal           │ │
│              │  │                                                          │ │
│              │  │  Dominio Metrologico *       Versao inicial              │ │
│              │  │  [Selecionar...        ▼]   [v1]  (automatico)          │ │
│              │  │                                                          │ │
│              │  └──────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  ┌─ Detalhes Tecnicos ─────────────────────────────────────┐ │
│              │  │                                                          │ │
│              │  │  Norma / Referencia Tecnica                              │ │
│              │  │  [____________________________________________]          │ │
│              │  │  ex: ABNT NBR ISO 3599, OIML R 111                      │ │
│              │  │                                                          │ │
│              │  │  Descricao do metodo                                     │ │
│              │  │  [                                                     ] │ │
│              │  │  [  Descreva o metodo de calibracao, pontos a medir,  ] │ │
│              │  │  [  criterios de aceitacao e condicoes ambientais.    ] │ │
│              │  │  [                                                     ] │ │
│              │  │                                              0/2000 chars│ │
│              │  │                                                          │ │
│              │  │  Condicoes ambientais minimas                            │ │
│              │  │  Temperatura: [__] a [__] °C    Umidade: [__] a [__] % │ │
│              │  │                                                          │ │
│              │  └──────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  ┌─ Instrumentos Aplicaveis ──────────────────────────────┐  │
│              │  │  Tipos de instrumento que usam este procedimento:      │  │
│              │  │                                                        │  │
│              │  │  [✓] Paquimetro       [ ] Micrômetro                   │  │
│              │  │  [ ] Trena            [ ] Projetor de perfil           │  │
│              │  │  [ ] Outro: [________________________]                 │  │
│              │  │                                                        │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  [Cancelar]                         [Salvar Procedimento]     │
│              │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## Layout — Detalhe do Procedimento (`/procedimentos/{procedimento}`)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar...                               🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │  Home > Laboratorio > Procedimentos > PROC-D-01               │
│              │                                                               │
│ ▼ Laborat.  │  Calibracao de Paquimetro               [Nova Versao] [Editar]│
│   ◼ Proced. │  PROC-D-01  •  Dimensional  •  v3  •  ●Ativo                 │
│              │                                                               │
│              │  ┌─ Tabs ──────────────────────────────────────────────────┐ │
│              │  │ [Dados Atuais]  [Versoes]  [OS Vinculadas]  [Audit Log] │ │
│              │  └─────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  --- Aba: Dados Atuais ---                                    │
│              │  ┌──────────────────────────────────────────────────────────┐ │
│              │  │  Codigo          PROC-D-01                               │ │
│              │  │  Nome            Calibracao de Paquimetro Escala Princ. │ │
│              │  │  Dominio         Dimensional                             │ │
│              │  │  Versao atual    v3  (criada em 10/03/2026)              │ │
│              │  │  Norma           ABNT NBR ISO 3599:2021                  │ │
│              │  │  Cond. Ambient.  Temp: 18°C a 22°C  UR: 40% a 70%      │ │
│              │  │  Instrumentos    Paquimetro                              │ │
│              │  │                                                          │ │
│              │  │  Descricao:                                              │ │
│              │  │  [Texto completo do metodo de calibracao...]             │ │
│              │  └──────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  --- Aba: Versoes ---                                         │
│              │  ┌──────────────────────────────────────────────────────────┐ │
│              │  │  Versao │ Data       │ Criado por   │ Motivo              │ │
│              │  │  v3     │ 10/03/2026 │ M. Engenheiro│ Atualizacao norma  │ ← atual
│              │  │  v2     │ 05/06/2025 │ M. Engenheiro│ Revisao de crit.   │ │
│              │  │  v1     │ 12/01/2025 │ M. Engenheiro│ Versao inicial     │ │
│              │  │                                                          │ │
│              │  │  [Ver diff v3 vs v2]                                     │ │
│              │  └──────────────────────────────────────────────────────────┘ │
│              │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## Layout — Modal: Nova Versao

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Backdrop bg-black/50                                                        │
│                                                                              │
│    ┌─ Modal ────────────────────────────────────────────────────────────┐   │
│    │  Nova Versao de PROC-D-01                                   [X]   │   │
│    │  A versao atual e v3. A nova versao sera v4.                      │   │
│    │  ─────────────────────────────────────────────────────────────    │   │
│    │                                                                   │   │
│    │  ℹ Voce pode editar os dados abaixo antes de salvar a nova       │   │
│    │    versao. Os dados da v3 sao preservados imutavelmente.          │   │
│    │                                                                   │   │
│    │  Motivo da nova versao *                                          │   │
│    │  [____________________________________________]                   │   │
│    │  ex: Atualizacao da norma ABNT NBR ISO 3599:2024                 │   │
│    │                                                                   │   │
│    │  [Campos editaveis: Nome, Norma, Descricao, Cond. Ambientais,    │   │
│    │   Instrumentos aplicaveis — pre-preenchidos da versao atual]      │   │
│    │                                                                   │   │
│    │  [Cancelar]                              [Criar Versao v4]        │   │
│    └────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Section Header | `component-patterns.md #31` | Secoes do formulario |
| Text Input | `component-patterns.md #5` | Codigo, Nome, Norma |
| Select | `component-patterns.md #7` | Dominio metrologico |
| Textarea | `component-patterns.md #6` | Descricao do metodo (max 2000 chars) |
| Number Input | `component-patterns.md #14` | Temperatura min/max, Umidade min/max |
| Checkbox Group | `component-patterns.md #8` | Instrumentos aplicaveis |
| Tabs | `component-patterns.md #23` | Dados Atuais / Versoes / OS / Audit Log |
| Table | `component-patterns.md #15` | Historico de versoes |
| Modal | `component-patterns.md #28` | Modal de nova versao |
| Button primary | `component-patterns.md #1` | "Salvar Procedimento", "Criar Versao vN" |
| Button secondary | `component-patterns.md #1` | "Cancelar" |
| Button outline | `component-patterns.md #1` | "Nova Versao" |

---

## Campos

| Campo | Tipo | Obrigatorio | Validacao |
|---|---|---|---|
| Codigo interno | Text Input | Sim | Unicidade no tenant; max 20 chars; sem espacos |
| Nome descritivo | Text Input | Sim | Max 200 chars |
| Dominio Metrologico | Select | Sim | 4 opcoes MVP |
| Norma / Referencia | Text Input | Nao | Max 200 chars |
| Descricao do metodo | Textarea | Nao | Max 2000 chars |
| Temperatura minima | Number Input | Nao | -100 a 200 °C |
| Temperatura maxima | Number Input | Nao | > Temperatura minima |
| Umidade minima | Number Input | Nao | 0 a 100 % |
| Umidade maxima | Number Input | Nao | > Umidade minima |
| Instrumentos aplicaveis | Checkbox Group | Nao | Multi-select |
| Motivo da versao (modal) | Text Input | Sim (ao criar nova versao) | Max 300 chars |

---

## Regras de Versionamento

| Regra | Comportamento |
|---|---|
| Versao inicial | Sempre v1; criada automaticamente |
| Nova versao | Incrementa: v1 → v2 → v3... |
| Versao anterior | Torna-se somente leitura; nunca deletada |
| OS em andamento | Usam a versao do procedimento no momento da criacao (snapshot) |
| OS futuras | Usam sempre a versao mais recente ativa |
| Codigo interno | Imutavel apos criacao; identidade do procedimento |

---

## Acessibilidade

- Modal com `role="dialog"`, `aria-modal="true"`, `aria-labelledby="modal-title"`
- Focus trap no modal; foco retorna ao botao "Nova Versao" ao fechar
- Textarea com `aria-describedby` apontando para contador de caracteres
- Tabela de versoes com `aria-label="Historico de versoes de {codigo}"`, linha atual com `aria-current="true"`
- Checkbox group com `role="group"`, `aria-labelledby="instrumentos-label"`

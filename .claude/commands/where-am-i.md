---
description: Mostra ao PM o estado atual de todos os slices do Kalibrium em linguagem de produto (R12). Último evento, arquivos presentes, próximo passo sugerido. Use a qualquer momento pra se orientar. Uso: /where-am-i [NNN].
---

# /where-am-i

## Propósito

Skill on-demand pro PM pedir estado atual dos slices a qualquer momento. Complementa o G-09 (que mostra 1-3 linhas automáticas no SessionStart) com relatório completo em PT-BR.

**Resolve G-10 da auditoria de operabilidade PM 2026-04-12.**

## Uso

```
/where-am-i          # lista todos os slices (ativos e concluídos)
/where-am-i 001      # foca em 1 slice específico
```

## O que mostra

Para cada slice com `specs/NNN/spec.md`:

1. **Título** em PT-BR (extraído do spec, limpa prefixo "slice NNN —")
2. **Estado**: "em andamento" ou "✓ concluído"
3. **Entregas** (arquivos presentes): spec / plano / verificação / revisão
4. **Últimos 3 eventos** da telemetria (traduzidos: "verificação automática aprovou", "revisão estrutural rejeitou", etc.)
5. **Próximo passo** sugerido em PT-BR:
   - `verify approved` → "rodar revisão estrutural (/review-pr NNN)"
   - `verify rejected` → "aguardando correção pelo implementer"
   - `review approved` → "pronto para merge (/merge-slice NNN)"
   - `review rejected` → "aguardando correção pelo implementer"
   - `merge` → "✓ slice concluído — gerar /slice-report NNN e /retrospective NNN"
   - **sem telemetria** → infere por artefatos: plan.md ausente → "spec preenchido, próximo passo: plano (/draft-plan NNN)"

Ao final, imprime resumo: "N em andamento | M concluído(s)".

Se `specs/` não existir ou estiver vazio: sugere criar primeiro slice via `/new-slice NNN "título"` ou pedir recomendação via `/next-slice`.

## Implementação

```bash
bash scripts/where-am-i.sh "$1"
```

(O argumento `$1` é opcional — se passado, filtra pro slice específico.)

## Quando usar

- **PM abriu o projeto depois de dias sem mexer** — quer saber onde parou
- **PM vai encerrar sessão** — confere estado antes de fechar
- **PM está confuso sobre qual comando rodar** — `/where-am-i` aponta próximo passo
- **Após falha ou interrupção** — recupera contexto perdido
- **Debug de fluxo** — vê quais arquivos e eventos estão presentes

## Relacionamento com G-09 (session-start)

| Aspecto | G-09 (automático) | G-10 (/where-am-i) |
|---|---|---|
| Dispara quando | SessionStart (boot da sessão) | sob demanda do PM |
| Output | 1-3 linhas no systemMessage JSON | relatório full em stderr |
| Detalhes | slice + próximo passo | slice + artefatos + últimos 3 eventos + próximo passo |
| Limite | 3 slices ativos | todos os slices |
| Escopo | só ativos | ativos + concluídos |

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Diretório `specs/` não existe ou está vazio | Informar PM que nenhum slice foi criado ainda. Sugerir `/new-slice NNN "título"` ou `/next-slice`. |
| Script `scripts/where-am-i.sh` falha ou não encontrado | Executar lógica equivalente inline (listar specs/*/spec.md, ler telemetria, inferir estado). |
| Telemetria vazia para um slice existente | Inferir estado apenas pelos artefatos presentes (spec.md, plan.md, verification.json, etc.). |

## Agentes

Nenhum — executada pelo orquestrador.

## Pré-condições

- Nenhuma — funciona em qualquer estado do projeto, inclusive vazio.

## Handoff

Nenhum — é só leitura. Após ler o relatório, PM decide o próximo passo livremente (criar novo slice, retomar existente, rodar verify/review/merge, etc.).

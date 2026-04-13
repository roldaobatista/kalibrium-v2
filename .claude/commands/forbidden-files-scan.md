---
description: Procura arquivos de instrução proibidos (R1) no repo — .cursorrules, AGENTS.md, GEMINI.md, copilot-instructions.md, .bmad-core/, etc. Use quando suspeitar de contaminação do harness. Uso: /forbidden-files-scan.
---

# /forbidden-files-scan

## Uso
```
/forbidden-files-scan
```

## O que faz

Executa o script `scripts/hooks/forbidden-files-scan.sh` manualmente. Mesma lógica do `SessionStart` hook, mas rodável sob demanda.

## Lista de padrões proibidos (R1)

Arquivos:
- `.cursorrules`
- `AGENTS.md`
- `GEMINI.md`
- `copilot-instructions.md`
- `.windsurfrules`
- `.aider.conf.yml`

Diretórios:
- `.bmad-core/`
- `.agents/`
- `.cursor/`
- `.continue/`

Além disso, grep por arquivos fora de `CLAUDE.md`/`docs/constitution.md`/`.claude/*` contendo padrões de instrução:
- `^You are`
- `^You're`
- `^Your role`
- `^As an agent`

## Output

Imprime lista de violações no terminal. Exit 0 se limpo, exit 1 se encontrou qualquer violação.

## Implementação
```bash
bash scripts/hooks/forbidden-files-scan.sh
```

## Quando rodar
- Ao clonar o repo em máquina nova
- Após merge de PR que tocou configs
- Quando `session-start.sh` falhar sem motivo aparente
- Antes de cada `/guide-check`

## Pré-condições

Nenhuma — pode ser executada a qualquer momento, em qualquer estado do projeto.

## Agentes

Nenhum — executada pelo orquestrador.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Script `scripts/hooks/forbidden-files-scan.sh` não existe ou sem permissão | Verificar integridade do harness. Rodar `/guide-check` para diagnóstico completo. O script é selado — se ausente, pode indicar tampering. |
| Scan encontra arquivos proibidos (exit 1) | Listar cada violação. Remover os arquivos proibidos (com confirmação do PM). Registrar incidente em `docs/incidents/` se a origem for desconhecida. |
| Falso positivo (arquivo legítimo contém padrão `^You are`) | Verificar se o arquivo está na lista de exceções (`CLAUDE.md`, `docs/constitution.md`, `.claude/*`). Se for legítimo, ajustar o script de scan (via procedimento de relock do harness). |

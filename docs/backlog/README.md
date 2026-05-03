# Backlog do Kalibrium V2

> Pasta onde mora **tudo** que o Roldão pediu, está sendo feito, ou já foi feito. Em pt-BR puro, sem jargão técnico.

## Estrutura

```
docs/backlog/
├── ROADMAP.md          ← épicos por trimestre, em ordem de prioridade
├── AGORA.md            ← o que está sendo feito agora
├── README.md           ← este arquivo
├── epicos/             ← épicos (grandes objetivos de produto)
├── historias/
│   ├── aguardando/     ← histórias aprovadas, esperando começar
│   ├── ativas/         ← histórias em andamento
│   └── feitas/         ← histórias aceitas e arquivadas
├── ideias/             ← caixa de entrada de ideias brutas (Roldão joga aqui sem se preocupar com formato)
└── aceites/            ← roteiros de aceite com imagens (saída do subagente e2e-aceite)
    └── imagens/        ← prints das telas
```

## Fluxo de uma demanda (do "tive uma ideia" até "está no servidor")

1. Roldão fala uma ideia → vai pra `ideias/` exatamente como falou
2. Maestra refina com 2-3 perguntas → vira história em `historias/aguardando/`
3. Roldão aprova a história → maestra rascunha plano técnico
4. Roldão aprova o plano → história vai pra `historias/ativas/`, AGORA.md atualiza
5. Subagente `executor` implementa, formata, testa
6. Subagente `revisor` audita (multi-tenant, migration, Livewire, testes)
7. Subagente `e2e-aceite` gera roteiro em `aceites/`
8. Roldão olha as imagens, decide "é isso" ou "não é isso"
9. Se aceito → Roldão autoriza subir, maestra sobe, história vai pra `historias/feitas/`

## Formato dos arquivos

Todos em **pt-BR sem jargão**. Sem código, sem stack trace, sem termo técnico cru. Cliente, equipamento, calibração, vencimento — não tenant, model, scope.

- Templates de história/épico/ideia ficam em `.claude/skills/` (cada skill já gera o formato certo).
- Roteiro de aceite tem template fixo dentro do subagente `e2e-aceite`.

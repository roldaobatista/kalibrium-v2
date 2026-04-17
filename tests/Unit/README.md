# tests/unit — placeholder para slices futuros

Este diretório é reservado para **unit tests** (Vitest) do cliente frontend
(React + TypeScript + Ionic). No slice-016 (E15-S02) **todos os ACs são de
scaffold/estrutura/E2E**, portanto nenhum unit test é adicionado aqui.

Os unit tests chegam a partir de E15-S06+ (SQLite, auth hooks, wipe logic),
quando houver lógica cliente de fato — não apenas stubs de página.

**Convenção futura (para quando chegar):**

- Runner: Vitest (coirmão natural do Vite).
- Arquivos: `tests/unit/**/*.test.ts` ou `tests/unit/**/*.test.tsx`.
- Rastreabilidade AC-ID (ADR-0017 Mudança 1): nome do teste ou
  `describe('AC-NNN: ...')` obrigatório.

Este README existe apenas para documentar a intenção e evitar que o diretório
vazio seja removido por limpeza automática.

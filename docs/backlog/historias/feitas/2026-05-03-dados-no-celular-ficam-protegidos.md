# História: Dados que o técnico salva no celular ficam protegidos

> **Origem:** segunda fatia do épico E15-S03 (REQ-SEC-002), explicitamente listada como "fora do escopo" da história `2026-05-03-tecnico-entra-no-app-do-celular`. A primeira fatia foi `2026-05-03-gerente-limpa-celular-roubado.md` (wipe remoto). Esta cobre a criptografia local dos dados em si.
>
> **ADR base:** ADR-0015 escolheu `@capacitor-community/sqlite` com SQLCipher (AES-256) para o storage criptografado.

## O que o cliente vai ver

Hoje, o que o app guarda no celular do Carlos (token de acesso, credenciais biométricas, dados do usuário logado) fica em `localStorage` — texto puro, qualquer pessoa que pegue o celular e plugue no computador consegue ler.

Esta história não muda nada visualmente pro Carlos. **Carlos não vê nenhuma diferença na tela** — o app continua igual. A mudança é embaixo do capô:

1. Quando Carlos faz login pela primeira vez, o app cria um **banco de dados local criptografado** dentro do celular. A chave que abre esse banco é gerada aleatoriamente, guardada nas credenciais seguras do sistema operacional (Keychain no iOS, Keystore no Android) — fora do alcance de qualquer aplicativo intruso.
2. A partir desse momento, qualquer dado que o app salvar localmente (token, dados do usuário, futuramente lista de OS, fotos, despesas) vai pra esse banco criptografado.
3. Se alguém pegar o celular do Carlos e copiar o banco de dados pro computador, vai ver só **bytes embaralhados** que ninguém consegue ler.
4. Quando o gerente disparar wipe remoto (história anterior), o banco inteiro é apagado, **e a chave também** — não dá pra recuperar.

Carlos só percebe que algo mudou se eu testar com ferramentas técnicas — mas o app continua tão rápido quanto antes pra ele.

## Por que isso importa

1. **LGPD exige.** Dados pessoais de cliente (nome, telefone, endereço, número da OS, fotos de equipamento) são dados sensíveis. Guardar em texto puro no celular do funcionário é violação direta da Lei Geral de Proteção de Dados.

2. **Celular roubado vira mineração de informação.** Sem criptografia local, mesmo após o wipe remoto, qualquer dado já sincronizado fica acessível durante a janela entre o roubo e o wipe (que pode ser 4 dias offline). Com criptografia, o ladrão recebe lixo.

3. **Vai ser usado por TODAS as histórias mobile daqui pra frente.** Lista de OS, fotos, despesas, certificados offline — tudo passa a salvar no banco criptografado. Sem essa infra, o resto do app não pode existir respeitando LGPD.

## Como saberemos que ficou pronto

1. **App mobile cria banco SQLite local criptografado no primeiro acesso.** Após login bem-sucedido, ao abrir a tela "Bem-vindo", o banco é criado se ainda não existir. Nome do arquivo: `kalibrium.db`. Chave: AES-256 derivada de bytes aleatórios e guardada no Keystore/Keychain via `@capacitor/preferences` ou plugin equivalente.

2. **Token e dados do user migram pra esse banco.** Em vez de `localStorage.kalibrium.token`, fica numa tabela `kv_store` (chave/valor) dentro do banco criptografado. `localStorage` deixa de armazenar dado sensível — só preferências não-sensíveis (ex: opt-out biometria).

3. **Logout limpa o banco.** Ao clicar "Sair", o banco é apagado E a chave do Keystore/Keychain também.

4. **Wipe remoto também limpa o banco.** A história anterior (wipe) já limpa `localStorage` e biometria. Nesta rodada, estendemos pra também apagar o banco SQLite criptografado e a chave.

5. **No navegador desktop (sem Capacitor SQLite), fallback gracioso.** Em desktop, o plugin SQLite não está disponível. App cai pra IndexedDB (também aceitável segundo ADR-0015 — desktop não é o caso crítico de risco). NÃO usar localStorage pra token nesse fallback.

6. **Helper `secureStorage` esconde a complexidade.** Os componentes do app (Login, Home, futuros) não interagem direto com SQLite — usam um service `secureStorage.set(key, value)` / `get(key)` / `remove(key)` / `clear()`. Esse service decide entre SQLite-cipher ou IndexedDB conforme a plataforma.

7. **Inspeção manual confirma criptografia.** Em ambiente de teste com app rodando no Android Studio, abrir o arquivo `/data/data/app.kalibrium.tecnico/databases/kalibrium.db` num editor hex e confirmar que **não há strings em claro** (nenhum email, nenhum token JWT visível). Print disso vira parte do roteiro de aceite.

8. **Plugin escolhido funciona na versão atual do Capacitor.** Verificar compatibilidade antes de decidir entre `@capacitor-community/sqlite` (escolha do ADR-0015) e alternativas mais leves (ex: `capacitor-secure-storage-plugin` se cobrir o caso).

## Fora do escopo desta história

-   **Migrar OS, clientes, fotos, despesas pro banco criptografado** — vai naturalmente nas próximas histórias quando essas entidades existirem no app.
-   **Sync engine** (E16) — sincronização cliente↔servidor é épico separado.
-   **Backup do banco em nuvem** — não é desejado (dados ficam só no celular do técnico até sync).
-   **Mudar autenticação (token/refresh/JWT vs Sanctum atual)** — fica como está.

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita

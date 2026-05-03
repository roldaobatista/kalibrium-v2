# História: Técnico tem uma tela inicial de verdade no app

> **Origem:** após login, hoje o técnico cai numa tela "Bem-vindo, {nome}" + botão Sair. Útil pra confirmar que o login funciona, inútil pra começar a trabalhar. Esta história entrega o shell visível pro técnico — espaço pras próximas histórias de OS, fotos, despesas.

## O que o cliente vai ver

Carlos faz login no app. Em vez da tela "Bem-vindo, Carlos" + botão Sair, ele vê uma tela com 4 partes:

1. **Cabeçalho fixo no topo:**

    - À esquerda: "Olá, Carlos"
    - À direita: ícone de menu (3 barras) que abre um drawer lateral.

2. **Resumo do dia (cards horizontais):**

    - **Ordens de hoje:** "0 ordens atribuídas pra hoje" (placeholder enquanto não há OS no app)
    - **Modo offline:** indicador pequeno "Online" / "Sem sinal" baseado no `navigator.onLine`.

3. **Área principal:**

    - Lista vazia: "Você não tem ordens de serviço atribuídas no momento. Quando o gerente atribuir, aparecem aqui."
    - Ícone ilustrativo no centro (clipboard vazio do Heroicons).

4. **Drawer lateral (3 barras):**
    - Avatar + nome + email do técnico no topo
    - "Início" (esta tela) — ativo
    - "Sair" no rodapé do drawer

Esta tela é o shell — ela não cria OS, não baixa nada. Mas deixa pronto o espaço pras próximas histórias plugarem.

A barra de status do celular (relógio, bateria) integra com a borda do app via `safe-area-inset-top` do iOS / status bar do Android (via Capacitor StatusBar plugin se preciso, mas não obrigatório nessa rodada).

## Por que isso importa

1. **Sem shell, app é stub.** "Bem-vindo, X" não é interface — é tela de teste. Pra Carlos abrir e fechar 5 vezes ao longo do dia, precisa parecer um aplicativo de verdade.

2. **Próximas histórias precisam disso.** Lista de OS, despesas, fotos — todas vão plugar nesse shell. Sem ele, cada história nova teria que decidir o layout do zero.

3. **Indicador online/offline prepara o terreno pra sync engine.** REQ-OFFLINE da arquitetura prevê 4 dias offline. O técnico precisa enxergar de cara se está conectado ou não.

4. **Drawer com Sair é o padrão UX nativo.** Botão "Sair" gigante no meio da tela polui. Mover pra drawer libera espaço útil.

## Como saberemos que ficou pronto

1. **Após login, técnico cai em `/home`** (ou seja, ainda na rota Home — só visual muda) com layout descrito.
2. **Cabeçalho mostra "Olá, {primeiro nome}"** (só primeiro nome — "Carlos Silva" vira "Olá, Carlos"). Ícone de menu (Heroicons `bars-3`) à direita.
3. **Card "Ordens de hoje" mostra "0 ordens atribuídas pra hoje"** com texto pequeno "Em breve, suas tarefas aparecem aqui."
4. **Card "Modo offline" indica online ou sem sinal** lendo `navigator.onLine`. Atualiza ao mudar (event listener).
5. **Lista vazia centralizada** com ícone clipboard (Heroicons outline), título "Sem ordens de serviço por enquanto" e subtítulo "Quando o gerente atribuir, aparecem aqui."
6. **Drawer lateral abre ao clicar no menu.** Mostra avatar (placeholder colorido com inicial do nome), nome completo, email, item "Início" ativo e botão "Sair" no rodapé.
7. **Drawer fecha** ao clicar fora ou clicar X no canto.
8. **Botão Sair** no drawer faz o mesmo logout de hoje (chama `secureStorage.clear()`, `biometric.clear()`, redireciona pra `/login`).
9. **Design system Kalibrium** aplicado — paleta primary, fonte Inter, espaçamentos do mobile.
10. **Funciona online e offline** (indicador muda). Não tenta chamar API se offline — só mostra o shell estático.

## Fora do escopo desta história

-   **Lista real de OS** — vira história separada (depende de E04/E16).
-   **Notificações push** — fica em história futura.
-   **Configurações no drawer** (mudar senha, biometria, idioma) — vira história futura.
-   **Trocar avatar / foto de perfil** — fica em história futura.

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita

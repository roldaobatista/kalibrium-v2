# História: Gerente tem uma tela inicial quando entra no painel

> **Origem:** gap percebido durante implementação das histórias de auth. Hoje, quando Marcelo (gerente) faz login no painel web, ele cai direto em `/mobile-devices`. Não tem visão geral, não tem ponto de retorno claro, não tem indicação de "o que precisa da minha atenção agora".

## O que o cliente vai ver

Marcelo termina o login. Em vez de cair direto na lista de celulares dos técnicos, ele vê uma tela inicial — uma "casa" do painel.

A tela mostra:

-   **Saudação no topo:** "Bom dia, Marcelo" (ou "Boa tarde / Boa noite", conforme a hora).
-   **Subtítulo:** "Aqui estão as coisas que precisam da sua atenção hoje."

Abaixo, **cards organizados em grade**, cada um destacando um número e um link:

1. **Celulares aguardando aprovação** — número grande (ex: "3"). Subtexto: "técnicos pediram acesso e estão esperando você liberar". Card todo é clicável → vai pra `/mobile-devices` filtrado em "aguardando".

2. **Técnicos com acesso ativo** — quantos celulares estão `approved` no momento. Link "Ver lista" → `/mobile-devices` filtrado em "aprovado".

3. **Celulares bloqueados** — quantos `revoked` ou `wiped_and_revoked`. Link "Ver lista" → filtrado.

4. **Atalhos rápidos:**
    - "Aprovar pedidos pendentes" (mesmo destino do card 1)
    - "Bloquear/limpar um celular" (vai pra `/mobile-devices` direto)
    - "Sair" (logout)

Quando não há nada pendente, o card 1 mostra "0" em verde com texto "Tudo em dia. Nenhum pedido aguardando."

Acima dos cards, um **menu lateral** (sidebar do design system) com itens reorganizados:

-   **Início** (esta nova tela) — ativo
-   **Celulares dos técnicos** — `/mobile-devices`
-   (futuras seções aparecem aqui — Clientes, OS, Certificados, etc.)

A sidebar já existe — só precisa adicionar "Início" como primeiro item e marcar como ativo nessa rota.

## Por que isso importa

1. **Sem tela inicial, o painel parece quebrado.** Cair direto numa lista de celulares dá a impressão de "isso é tudo que tem aqui?". Uma home dá identidade ao painel e mostra que vai crescer.

2. **Marcelo quer ver pendências de cara.** Hoje ele tem que clicar em "Celulares" pra descobrir se tem pedido pendente. Com a home, ele vê o número assim que loga.

3. **Atalhos > navegar manualmente.** Se Marcelo entra com pressa pra liberar o Carlos, "Aprovar pedidos pendentes" no topo da home é melhor do que "menu → Celulares → filtrar → encontrar". Funcionalidade igual, atrito menor.

## Como saberemos que ficou pronto

1. **Após login, gerente vai pra `/dashboard`** (ou `/`, conforme convenção do projeto). Não cai mais em `/mobile-devices`.

2. **Saudação adapta-se à hora do servidor.** Antes de meio-dia: "Bom dia". Entre meio-dia e 18h: "Boa tarde". Depois de 18h: "Boa noite". Em pt-BR.

3. **Card "celulares aguardando" mostra contagem real.** Tocar no card leva a `/mobile-devices?status=pending`.

4. **Card "técnicos com acesso ativo" mostra contagem de approved.**

5. **Card "celulares bloqueados" mostra contagem de revoked + wiped_and_revoked.**

6. **Estado vazio:** quando nada está aguardando, o card mostra "0" em verde com "Tudo em dia. Nenhum pedido aguardando.".

7. **Sidebar "Início" como primeiro item, ativo na nova rota.** Item "Celulares dos técnicos" continua funcionando. Cliques entre eles mantêm o usuário logado.

8. **Atalhos no rodapé/canto da home:** botões "Aprovar pedidos pendentes" e "Sair" funcionando.

9. **Dashboard só visível pra gerentes.** Técnicos comuns (se um dia logarem na web) recebem 403 ou veem versão diferente. Por enquanto, basta exigir role `gerente`.

10. **Multi-tenant:** contadores escopados pelo tenant atual. Gerente do laboratório Acme não vê dados do laboratório Beta.

## Fora do escopo desta história

-   **Cards de outras seções** (clientes, OS, certificados) — vão aparecer naturalmente conforme as histórias forem entregues.
-   **Gráficos / linha do tempo / atividade recente** — vira história separada quando houver demanda real.
-   **Personalização da home pelo gerente** — vira história futura.
-   **Notificações em tempo real** — fica pra quando tiver sync engine (E16).

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita

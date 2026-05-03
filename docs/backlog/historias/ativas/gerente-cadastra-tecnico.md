# História: Gerente cadastra técnico no laboratório

> **Origem:** dependência prática descoberta enquanto entregava as histórias de auth e dashboard. Hoje, técnicos só existem no sistema se alguém criar direto no banco — não há tela pra Marcelo (gerente) cadastrar Carlos antes de Carlos tentar entrar no app.

## O que o cliente vai ver

Marcelo entra no painel. Na sidebar à esquerda, há um item novo: **"Técnicos"**.

Clica.

Vê uma lista dos técnicos já cadastrados no laboratório dele:

| Nome           | E-mail                  | Status  | Última atividade |
| -------------- | ----------------------- | ------- | ---------------- |
| Carlos Silva   | carlos@laboratorio.com  | Ativo   | há 2 horas       |
| Juliana Mendes | juliana@laboratorio.com | Ativo   | há 3 dias        |
| Pedro Antigo   | pedro@laboratorio.com   | Inativo | há 6 meses       |

No topo direito, botão **"+ Cadastrar técnico"**.

Marcelo clica. Abre um formulário simples:

-   **Nome completo** (campo obrigatório)
-   **E-mail** (obrigatório, único)
-   **Senha inicial** (obrigatório, mínimo 8 caracteres com pelo menos 1 número) — Marcelo precisa anotar e passar pro técnico, OU clicar num botão "Gerar e enviar por e-mail" que sorteia uma senha temporária e manda no e-mail do técnico junto com link de "Defina sua senha definitiva no primeiro acesso".

Para esta primeira fatia, **fica só a senha digitada pelo gerente**. Senha temporária por e-mail vira história separada se Roldão pedir.

Marcelo preenche, clica em **"Cadastrar"**.

O técnico aparece na lista com status "Ativo". Marcelo passa email + senha pro Carlos. Carlos abre o app, faz login, cai em "aguardando aprovação" (fluxo já existente).

Cada linha da lista também tem ações:

-   **Editar** — abre modal/tela com mesmos campos (sem mexer na senha — pra trocar senha existe outro fluxo).
-   **Desativar** — pergunta "tem certeza?", e se confirmar, técnico fica `inactive`. Se ele tentar logar, recebe 403 "Sua conta foi desativada. Procure o gerente." Devices dele ficam intocados, mas nenhum login passa.
-   **Reativar** — só aparece em técnicos `inactive`. Volta pra `active`.

## Por que isso importa

1. **Sem cadastro, sistema é inútil pro cliente real.** Hoje, Marcelo precisaria pedir pro Roldão criar usuário no banco. Inviável em produção.

2. **Desativar é diferente de deletar.** Funcionário que sai não deve ter o histórico apagado (LGPD permite manter dados de OS pra fins fiscais). Status "inativo" preserva dados e impede acesso — sem precisar deletar nada.

3. **Limita criação por tenant.** Marcelo do laboratório Acme só vê e cadastra técnicos do Acme. Não enxerga o Beta.

4. **Política de senha forte de cara.** Mesma regra do reset (8 chars + 1 número) — impede técnico de receber "12345" como senha inicial.

## Como saberemos que ficou pronto

1. **Item "Técnicos" aparece na sidebar** entre "Início" e "Celulares dos técnicos". Ícone Heroicons compatível.

2. **Lista mostra nome, e-mail, status (badge ativo/inativo) e última atividade** de cada técnico do tenant.

3. **Filtro por status** (todos / ativos / inativos) e busca por nome/email funcionam.

4. **Botão "+ Cadastrar técnico" abre formulário modal ou rota dedicada** com nome, email, senha. Validação local + server. Email único (no tenant — se já existe Carlos no tenant, dá erro).

5. **Cadastro cria registro `User` + `TenantUser` no tenant atual com role `tecnico`.** Não cria com role `gerente`.

6. **Editar técnico** mantém dados intactos (especialmente `MobileDevice` vinculados — não apaga celulares do técnico).

7. **Desativar** muda status do `TenantUser` pra `inactive`. Login do técnico passa a retornar 403 com mensagem em pt-BR. Devices não são revogados (gerente decide se quer rodar wipe separado).

8. **Reativar** volta status `active`. Login funciona de novo (devices aprovados continuam aprovados).

9. **Auditoria** registra cada cadastro / edição / desativação / reativação no log.

10. **Multi-tenant:** tudo escopado pelo tenant ativo. Gerente do Acme não cadastra/lê/edita técnicos do Beta.

11. **Policy:** só `gerente` acessa essas telas. Técnico recebe 403.

12. **Card "Técnicos com acesso ativo" do dashboard atualiza em tempo real** ao cadastrar/desativar — basta recarregar pra ver. (Não precisa websocket, refresh manual basta.)

## Fora do escopo desta história

-   **Senha temporária + link de definir senha por e-mail** — vira história futura ("convite de técnico").
-   **Definir limite de quantos técnicos por plano de assinatura** — fica em história separada ("plano e cobrança").
-   **Importação CSV de técnicos** — fica em história futura.
-   **Trocar senha do técnico via painel do gerente** — fica em história separada (segurança sensível, precisa decidir se gerente reseta direto ou só dispara link de reset).

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita

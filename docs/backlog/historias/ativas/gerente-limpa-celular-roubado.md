# História: Gerente limpa celular roubado ou perdido

> **Origem:** primeira fatia do épico E15-S03 (REQ-SEC-002 / wipe remoto), explicitamente listada como "fora do escopo" da história `2026-05-03-tecnico-entra-no-app-do-celular`. Esta história cobre só a parte do wipe — a criptografia local dos dados salvos no celular vira história separada (depende de SQLite local que ainda não foi montado).

## O que o cliente vai ver

Carlos (técnico) perdeu o celular. Ele liga pra Marcelo (gerente do laboratório) e avisa.

Marcelo abre o painel do Kalibrium, vai em "Celulares dos técnicos", acha o celular do Carlos na lista e clica num botão novo: **"Limpar e bloquear"**.

O sistema pergunta:

> "Tem certeza que quer apagar todos os dados do Kalibrium do Samsung Galaxy A54 do Carlos e bloquear o acesso? Essa ação não pode ser desfeita."

Marcelo confirma.

Da próxima vez que o celular abrir o app (mesmo que seja o ladrão), assim que tentar carregar qualquer dado, o app:

1. Recebe do servidor a ordem de limpeza.
2. Apaga o token salvo, as credenciais biométricas, qualquer dado em cache local.
3. Mostra uma tela curta: "Este celular foi bloqueado pelo seu laboratório. Entre em contato com o gerente."
4. Não deixa fazer mais nada.

O celular do Carlos volta a ficar como se o app tivesse sido recém-instalado.

Quando Carlos compra um celular novo, ele instala o app, faz login com as mesmas credenciais, e o pedido de aprovação aparece de novo pro Marcelo (igual da primeira vez).

## Por que isso importa

1. **Celular roubado = porta aberta pra roubar dados de cliente.** O Kalibrium guarda nome, telefone e endereço de cliente, dados de calibração, fotos de equipamento. Se o aparelho cai na mão errada, o gerente precisa ter como cortar o acesso na hora.

2. **Funcionário desligado precisa perder acesso imediato.** Hoje, se Carlos for demitido, o token dele continua valendo até expirar (4 dias). O wipe remoto resolve isso — Marcelo bloqueia no painel e na próxima vez que o app abrir, perde o acesso.

3. **LGPD exige.** Manter dados pessoais em celular após o vínculo com a empresa cair é violação. Wipe remoto é o mecanismo pra atender o requisito legal.

## Como saberemos que ficou pronto

Cada item validado por imagem real (print da tela) no roteiro de aceite:

1. **Botão "Limpar e bloquear" aparece na linha do celular no painel do gerente.** Tanto pra celular `aprovado` quanto `aguardando` quanto `bloqueado` (no caso de bloqueado, é pra reforçar o wipe se ele ainda não foi efetivado).

2. **Confirmação antes de limpar.** Modal pergunta "tem certeza" mostrando nome do técnico e modelo do celular.

3. **Após confirmar, status do celular muda pra "bloqueado e limpo"** (novo status — diferente de "bloqueado" sozinho, pra deixar claro que a ordem de limpeza foi enviada). Badge na tabela mostra com cor vermelha mais escura.

4. **Quando o app do celular tentar fazer qualquer chamada autenticada ao servidor, recebe sinal de wipe.** O backend retorna 401 com um campo extra `wipe: true`. O app limpa tudo (token, credenciais biométricas, localStorage do app) e redireciona pra tela de bloqueio.

5. **Tela de bloqueio no app mobile** mostra: "Este celular foi bloqueado pelo seu laboratório. Entre em contato com o gerente." Sem campo de login — é uma tela morta.

6. **Reinstalar o app no mesmo celular volta o ciclo do zero.** Se Carlos perdeu, achou de volta e reinstalou, ele consegue fazer login normalmente, mas precisa de nova aprovação do gerente (novo `MobileDevice` `pending`).

7. **Ações registradas no log de auditoria.** Quem mandou o wipe, quando, em qual celular, qual técnico.

## Fora do escopo desta história

-   **Criptografia local dos dados salvos no celular** (REQ-SEC-002) — vira história separada quando houver SQLite local com SQLCipher.
-   **Notificação ao técnico** ("seu celular foi bloqueado pelo gerente Marcelo às 14h32") — vira história futura se aparecer demanda.
-   **Auto-wipe por inatividade** (celular que não conecta há 30 dias é wiped automaticamente) — vira história futura.

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita

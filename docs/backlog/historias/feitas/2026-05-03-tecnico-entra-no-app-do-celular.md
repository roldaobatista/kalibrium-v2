# História: Técnico consegue entrar no app do celular

> **Origem:** primeira história visível ao cliente do épico E15 (app móvel offline). Equivale ao slice E15-S02 do roadmap antigo (`docs/product/roadmap.md`). Cobre os requisitos REQ-SEC-001 (biometria), REQ-SEC-004 (device binding) e REQ-SEC-005 (sessão longa) do escopo do MVP.
>
> **Pré-requisito técnico (faço sozinho, não precisa virar história):** criar o projeto do app móvel (React + Ionic + Capacitor) com a estrutura básica buildando pra Android e iOS. Sem isso, não tem onde o login acontecer. Equivale ao slice E15-S01 do roadmap.

## O que o cliente vai ver

O técnico do laboratório (Carlos, Juliana, qualquer pessoa que vai a campo ou trabalha na bancada) baixa o app no celular dele pela primeira vez. Quando abre, vê uma tela limpa pedindo:

-   E-mail
-   Senha
-   Botão "Entrar"

Quando ele clica em entrar pela primeira vez naquele celular específico, o app diz:

> "Este celular ainda não foi autorizado pelo seu laboratório. Pedido de autorização enviado para o gerente. Você vai poder entrar assim que ele aprovar."

O gerente recebe um aviso (no e-mail e no painel dele): "Carlos está pedindo pra usar o Kalibrium no celular Samsung Galaxy A54 dele. Autorizar?". Ele clica "autorizar".

Carlos tenta entrar de novo. Dessa vez funciona. O app pergunta:

> "Quer usar sua digital pra entrar nas próximas vezes em vez de digitar a senha toda hora?"

Se ele aceitar, na próxima abertura o app só pede a digital (ou face, dependendo do celular) e entra direto.

Carlos pode trabalhar **4 dias inteiros sem precisar logar de novo**, mesmo se ficar sem internet. Quando voltar a sinal e abrir o app, ele continua logado.

Se ele errar a senha 5 vezes seguidas, o app trava por 15 minutos pra impedir que alguém tente adivinhar.

## Por que isso importa

1. **Sem login funcionando, nada do app móvel funciona.** É o portão de entrada de tudo — calibração em campo, despesa, foto, assinatura, certificado offline. Tudo depende do técnico estar identificado.

2. **Operação de campo exige confiar no celular pessoal do técnico.** Diferente do laboratório (onde o computador é da empresa), o celular geralmente é pessoal. A aprovação do dispositivo pelo gerente protege contra: técnico instalar o app no celular do filho, ex-funcionário continuar acessando depois de sair, celular roubado virar porta de entrada.

3. **Biometria não é luxo, é produtividade.** Técnico que precisa digitar senha de 12 caracteres toda hora vai escolher a senha "12345" ou anotar num papel. Biometria evita essa armadilha sem prejudicar segurança.

4. **4 dias offline é o cenário real.** O cliente do MVP tem técnico que vai pra mina, usina, fazenda, obra rural — lugares sem 4G. Se a sessão expirar em 24h como num site comum, o técnico fica trancado fora do próprio sistema no meio do trabalho.

## Como saberemos que ficou pronto

Cada item abaixo precisa ser validado por imagem real (print da tela) no roteiro de aceite:

1. **Tela de login aparece quando o app abre pela primeira vez.** O técnico vê um campo de e-mail, um campo de senha, e um botão "Entrar". Sem mais nada na tela (sem registro de novo usuário, sem opções de redes sociais — login é estritamente para usuário já cadastrado pelo gerente).

2. **Primeiro login pede aprovação do gerente.** Quando o técnico digita email + senha corretos pela primeira vez naquele celular, o app mostra mensagem em pt-BR explicando que o aparelho precisa ser autorizado. Não deixa entrar antes da aprovação.

3. **Gerente recebe aviso e aprova.** O gerente vê uma lista de "celulares pedindo autorização" com nome do técnico, modelo do aparelho, data e hora do pedido. Clica em "autorizar" e o pedido some da lista.

4. **Após aprovação, técnico entra normalmente.** Ele tenta de novo, agora entra direto na tela inicial do app (que pode ser uma tela vazia "bem-vindo" — esta história não cobre o que tem na tela inicial, só que ele consegue chegar lá).

5. **App pergunta se quer usar biometria.** Logo depois do primeiro login bem-sucedido, o app oferece configurar entrada por digital ou face. Técnico pode aceitar ou recusar (se recusar, sempre vai entrar com senha).

6. **Quando aceita biometria, próxima abertura usa biometria.** O técnico fecha e abre o app. Em vez de pedir senha, pede digital. Funciona.

7. **Senha errada 5 vezes trava login por 15 minutos.** Se errar 5 vezes seguidas, o app mostra "muitas tentativas, tente novamente em 15 minutos" e bloqueia o botão de entrar até passar o tempo.

8. **Sessão dura 4 dias sem internet.** Técnico entra no app, sai do alcance de internet, fica 4 dias usando o celular offline (pode-se simular isso em ambiente de teste mudando a hora do aparelho), volta a abrir o app — continua logado, não pede senha de novo.

9. **Cada celular é registrado uma vez.** Se Carlos perde o celular antigo e compra um novo, ele faz login no novo e o pedido de aprovação aparece de novo pro gerente — independente de já ter sido aprovado no antigo.

10. **Funciona em Android e iPhone.** Caminho 1 a 9 acima testado nos dois sistemas.

## Fora do escopo desta história (vão pra próximas)

Pra evitar inchar esta história, ficam pra histórias seguintes:

-   **Wipe remoto** (gerente bloqueia/limpa um celular roubado à distância) — vira história separada do mesmo épico.
-   **Criptografia dos dados salvos no celular** (dados de OS, fotos, despesas guardados protegidos no aparelho) — REQ-SEC-002, vira história separada porque depende do app já ter dados pra proteger.
-   **Recuperação de senha esquecida** (link no e-mail) — vira história separada.
-   **Trocar usuário no mesmo celular** (gerente entrar no celular do técnico temporariamente) — vira história separada se aparecer demanda real.

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita

# História: Técnico recupera senha esquecida

> **Origem:** explicitamente listada como "fora do escopo" da história `2026-05-03-tecnico-entra-no-app-do-celular`. Reativada agora porque é dependência clara antes de qualquer cliente real começar a usar — sem isso, qualquer técnico que esquecer a senha precisa pedir reset manual ao gerente.
>
> **Cobertura:** E15 / E02 (autenticação). Esta história complementa o login do app móvel E o login web (gerente).

## O que o cliente vai ver

Carlos esqueceu a senha. Ele tenta entrar no app do celular, erra três vezes, e percebe que não lembra.

Logo abaixo do botão **"Entrar"**, ele vê um link discreto: **"Esqueci minha senha"**.

Clica.

Aparece uma tela nova:

> "Vamos enviar um link pra você redefinir sua senha. Confirme seu e-mail abaixo."
>
> [campo e-mail] [botão "Enviar"]

Ele digita `carlos@laboratorio.com` e clica em "Enviar".

O app responde:

> "Se este e-mail estiver cadastrado, você vai receber em alguns minutos uma mensagem com o link pra redefinir a senha. Confira sua caixa de entrada (e a pasta de spam, se não encontrar)."

Carlos abre o e-mail. Mensagem com:

-   Logo do Kalibrium
-   "Olá Carlos, recebemos um pedido pra redefinir sua senha."
-   Botão grande **"Redefinir senha"**.
-   "Este link vale por 1 hora. Se você não pediu isso, ignore este e-mail — sua senha continua a mesma."

Carlos clica. O navegador abre uma página web com:

-   "Defina sua nova senha"
-   Campo "Nova senha" (com regras de força: mínimo 8 caracteres, pelo menos 1 número)
-   Campo "Confirme a nova senha"
-   Botão "Salvar nova senha"

Ele digita uma senha, confirma, salva. Aparece:

> "Pronto! Sua senha foi atualizada. Volte ao app e entre normalmente."

Volta ao app, faz login com a nova senha — entra direto (o device dele já está aprovado).

**Mesmo fluxo serve pro gerente** que esqueceu a senha do painel web — botão "Esqueci minha senha" também na tela de login web.

## Por que isso importa

1. **Sem isso, qualquer reset vira chamado de suporte.** Cliente real vai esquecer senha. Sem auto-recuperação, o gerente vira atendente de TI.

2. **Segurança boa, não burra.** A resposta "se o e-mail estiver cadastrado, você recebe" não revela se o e-mail existe na base — protege contra mineração. Combinado com link de 1 hora e único uso, é o padrão da indústria.

3. **Mesma infra atende mobile E web.** O link no e-mail aponta pra página web. Funciona pros dois canais sem duplicar código.

4. **Token de redefinição é diferente do token de sessão.** Não confunde com Sanctum. Tabela `password_reset_tokens` (já existe no Laravel padrão) cuida disso — só falta verificar e expor.

## Como saberemos que ficou pronto

1. **Link "Esqueci minha senha" aparece na tela de login do app mobile.** Discreto, abaixo do botão Entrar, cor `var(--kb-primary-600)`.

2. **Mesmo link aparece na tela de login web do gerente.** Com tratamento visual coerente (Tailwind + design system).

3. **Tela "Pedir reset" pede e-mail.** Validação local (formato de e-mail). Mensagem genérica após enviar (não revela se e-mail existe).

4. **E-mail enviado contém link válido por 1 hora.** Implementado via `Password::sendResetLink()` do Fortify/Laravel. Template em pt-BR. Logo Kalibrium no topo.

5. **Página web "Definir nova senha" funciona.** Nova senha com mínimo 8 caracteres + 1 número. Confirmação igual. Token único — se usar de novo, dá erro "este link já foi usado ou expirou".

6. **Após redefinir, redireciona pra tela de login web** com toast "Senha atualizada. Entre com a nova senha."

7. **Técnico volta ao app, entra com nova senha — funciona.** Device já aprovado anteriormente continua aprovado (não reseta o ciclo).

8. **Tentar usar token expirado mostra erro em pt-BR.** "Este link expirou. Peça um novo na tela de login."

9. **Tentar pedir reset 6 vezes em 1h pelo mesmo IP/email é bloqueado.** Throttle padrão do Fortify (já existe).

10. **Multi-tenant: e-mail enviado e token criado respeitam o tenant.** Se Carlos pertence ao tenant Acme e existe outro Carlos no tenant Beta, cada um redefine só a sua.

## Fora do escopo desta história

-   **2FA na recuperação** (recuperação por celular além do e-mail) — vira história futura se aparecer demanda.
-   **Política de senha forte avançada** (caracteres especiais, sem repetição, etc.) — fica em história separada de "endurecer política de senha".
-   **Histórico de senhas (não pode reusar últimas 5)** — fica em história separada.

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita

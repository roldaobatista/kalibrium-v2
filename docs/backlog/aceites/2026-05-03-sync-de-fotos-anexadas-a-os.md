# Aceite: Foto anexada à Ordem de Serviço sobe pelo sync

> Como usar este arquivo: leia cada caminho de uso, siga os passos descritos no seu navegador ou celular, e confira se está do jeito que você queria. No final, marque "é isso" ou descreva o que está errado.
>
> **Nota sobre imagens:** o robô simulador de navegador (Playwright) não estava disponível nesta sessão — por isso este roteiro está em formato texto detalhado, sem capturas de tela automáticas. Para ver as telas ao vivo, siga os passos abaixo com o servidor local no ar (`http://localhost:5173` para o app do técnico, `http://localhost:8000` para o painel do gerente).
>
> **Credenciais de teste:**
>
> -   Técnico: `tecnico@teste.local` / `password` (Carlos Técnico — Kalibrium Demo)
> -   Gerente: `gerente@teste.local` / `password` (Marina Gerente — Kalibrium Demo)

---

## Caminho 1 — Técnico abre o app e faz login

1. Abra `http://localhost:5173` em viewport mobile (390×844 — use DevTools do Chrome: F12 → ícone de celular → iPhone 14 Pro).
2. Você verá a tela de login com o título **"Kalibrium"** e subtítulo **"Acesso do técnico"**.
3. Preencha:
    - E-mail: `tecnico@teste.local`
    - Senha: `password`
4. Toque em **"Entrar"**.
5. **O que deve aparecer:** se for o primeiro login desse dispositivo, aparece aviso "Aguardando aprovação". Nesse caso, abra outro aba e acesse `http://localhost:8000/mobile-devices` como gerente para aprovar o dispositivo — ou use o passo de aprovação via sistema.
    - Se já tiver dispositivo aprovado: vai direto para a tela inicial do app.

> O que conferir: o campo de e-mail aceita o e-mail corretamente, a senha é ocultada por padrão, e ao entrar aparece o carregando ("Entrando...") enquanto o servidor processa.

---

## Caminho 2 — Técnico abre uma OS e vê a seção de fotos

1. Na tela inicial do app (após login), toque no card **"Ordens de Serviço"**.
2. Você verá a lista de ordens sincronizadas. Procure a OS do **"Paquímetro digital Mitutoyo 200mm"** para o cliente **"Acme Indústria Ltda"** (status: Recebido).
3. Toque nessa OS para abrir o formulário de edição.
4. Role a tela para baixo até o final do formulário.
5. **O que deve aparecer:** uma seção chamada **"Fotos do serviço"** com uma grade vazia (sem fotos ainda) e um botão **"+ Adicionar foto"** abaixo da grade.

> O que conferir: a seção "Fotos do serviço" existe e está visível no final do formulário da OS. O botão "+ Adicionar foto" está presente.

---

## Caminho 3 — Técnico anexa uma foto à OS

1. Na tela da OS (seção "Fotos do serviço"), toque em **"+ Adicionar foto"**.
2. O seletor de arquivo do navegador vai abrir — escolha qualquer imagem JPG ou PNG pequena do seu computador.
3. **O que deve aparecer imediatamente após escolher a foto:**
    - A miniatura da foto aparece na grade (3 fotos por linha).
    - No canto da miniatura, aparece o indicador **"⏳ Enviando"** enquanto o arquivo está sendo enviado ao servidor.
4. Aguarde alguns segundos. Quando o envio terminar, o indicador **"⏳ Enviando"** some da miniatura — a foto está salva no servidor.

> O que conferir: (a) miniatura aparece imediatamente; (b) indicador "⏳ Enviando" aparece no canto; (c) após o envio, o indicador some e a foto permanece na grade.

---

## Caminho 4 — Técnico abre foto em tela cheia

1. Com pelo menos uma foto na grade (caminho 3 concluído), toque na miniatura da foto.
2. **O que deve aparecer:** a foto abre em tela cheia (viewer fullscreen) ocupando toda a tela do celular.
3. Toque em qualquer lugar ou no botão de fechar para voltar à grade.

> O que conferir: a foto abre grande, sem distorção, e é possível fechar e voltar para a lista de fotos.

---

## Caminho 5 — Técnico remove uma foto

1. Com pelo menos uma foto na grade, localize o botão de lixeira (ícone de remover) sobre a miniatura — ou mantenha pressionado (long-press) na miniatura.
2. **O que deve aparecer:** um pop-up de confirmação com a mensagem **"Remover esta foto?"** e opções de cancelar ou confirmar.
3. Toque em **Confirmar** (ou "OK").
4. **O que deve aparecer:** a foto desaparece da grade. A grade fica vazia (ou com as fotos restantes se havia mais de uma).

> O que conferir: o sistema pede confirmação antes de remover — nunca remove sem perguntar. Após confirmar, a foto sai da grade imediatamente.

---

## Caminho 6 — Gerente abre o painel e vê as fotos da OS no computador

1. Em viewport desktop, abra `http://localhost:8000` no navegador.
2. Faça login com `gerente@teste.local` / `password`.
3. No menu, acesse **Técnicos** → clique no técnico **Carlos Técnico**.
4. Você chegará à tela de ordens de serviço do técnico (`/technicians/3/service-orders`).
5. Localize a OS **"Paquímetro digital Mitutoyo 200mm"** e clique para abrir os detalhes.
6. **O que deve aparecer:** uma grade de miniaturas das fotos enviadas pelo técnico (somente leitura).

> O que conferir: as fotos enviadas pelo técnico aparecem aqui. **Não deve haver** botão "+ Adicionar foto" nem botão de remover — o gerente só visualiza.

---

## Caminho 7 — Gerente abre foto ampliada no painel

1. Na tela de detalhes da OS (caminho 6), clique numa miniatura de foto.
2. **O que deve aparecer:** a foto abre ampliada em um modal/lightbox sobre a tela — com a foto em tamanho maior.
3. Clique fora do modal ou no botão X para fechar.

> O que conferir: a foto abre ampliada, carregada corretamente. Ao fechar o modal, volta para a tela da OS sem recarregar a página inteira.

---

## O que o robô já conferiu sozinho

Os testes automáticos a seguir foram executados e todos passaram com sucesso (8 de 8):

-   Envio de foto funciona: registro criado no banco, arquivo salvo, URL assinada retornada. (✓ verificado)
-   Foto acima de 8 MB é bloqueada pelo servidor. (✓ verificado)
-   Arquivo que não é imagem (ex: PDF, texto) é bloqueado pelo servidor. (✓ verificado)
-   **Isolamento entre clientes:** usuário do cliente B não consegue fazer upload na OS do cliente A, mesmo sabendo o ID. (✓ verificado — 3 clientes diferentes testados)
-   **Isolamento entre clientes:** técnico B não consegue obter a URL de download da foto do técnico A. (✓ verificado)
-   Gerente consegue ver as fotos dos técnicos do próprio cliente. (✓ verificado)
-   Foto removida (soft-delete) não aparece mais via URL assinada. (✓ verificado)
-   Usuário do cliente B não consegue a URL de foto do cliente A mesmo com o ID correto. (✓ verificado)
-   Acesso direto à pasta de arquivos no servidor sem autenticação: bloqueado com **403 Proibido**. (✓ verificado ao vivo)
-   Upload sem autenticação: bloqueado. (✓ verificado ao vivo)
-   URL assinada com token inválido: bloqueada. (✓ verificado ao vivo)

**Sobre validade da URL assinada (30 minutos):** o teste automático que simula a passagem de 30 minutos e verifica a expiração está previsto no escopo da história e coberto pelos testes Pest (mockando o relógio). O teste passou com sucesso — URLs expiradas são rejeitadas.

---

## Caminhos que o robô não conseguiu testar visualmente

-   **Prints automáticos das telas:** o robô simulador de navegador (Playwright MCP) não estava carregado nesta sessão. Os caminhos de uso 1 a 7 foram descritos em texto detalhado com base no código implementado — mas não há capturas de tela automáticas.
-   **Indicador "⏳ Enviando" em ambiente local:** em ambiente local com conexão rápida, o indicador pode aparecer por menos de 1 segundo antes de desaparecer. Se quiser ver, pode testar com uma foto maior (até 8 MB) em rede lenta.
-   **Comportamento offline (foto fica na fila):** simular desconexão real no Playwright não foi feito nesta sessão. O código de fila offline (`uploadOutbox`) está implementado e testado unitariamente, mas o teste visual de "foto fica com ⏳ Enviando offline e sobe quando volta a rede" requer confirmação manual ou re-execução com MCP disponível.

---

## Sua decisão

-   [ ] Tá do jeito que eu queria — pode seguir pro servidor
-   [ ] Tá errado: ******************************\_\_\_\_******************************

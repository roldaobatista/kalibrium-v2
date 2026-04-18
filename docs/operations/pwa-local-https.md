# PWA — Servidor HTTPS Local para Testes

Slice 017 · Operação

## Por que HTTPS local

Service Workers só registram em **contextos seguros**:

- `https://...` (qualquer host), ou
- `http://localhost` / `http://127.0.0.1` (exceção especial dos browsers).

Para testar o PWA a partir de **outro dispositivo** na rede local (celular Android / iPad / IP do PC), precisamos de HTTPS. Isso afeta:

- Teste E2E em device real (`npm run test:e2e` com `baseURL=https://<ip>:4173`).
- Teste de Lighthouse em CI (`npm run test:lighthouse`).
- Validação manual de instalação ("Adicionar à tela inicial").

## Como usar

```bash
# 1. Gera o build de produção
npm run build

# 2. Sobe servidor HTTPS (porta 4173 default)
npm run serve:https
```

Saída esperada:

```
[serve-https] usando cert de fonte: cache|env|openssl
[serve-https] https://localhost:4173/
[serve-https] servindo /caminho/para/dist
```

O script `scripts/pwa/serve-https.mjs` tem 3 camadas de fallback para o certificado:

### Camada 1 — Certificado explícito via variáveis de ambiente

Se você já gerou um cert com [mkcert](https://github.com/FiloSottile/mkcert), aponte via env:

```bash
export KALIB_HTTPS_CERT=/caminho/para/cert.pem
export KALIB_HTTPS_KEY=/caminho/para/key.pem
npm run serve:https
```

**Preferido para uso repetido:** mkcert instala uma CA raiz no seu sistema, então o certificado é aceito sem avisos de segurança no browser.

### Camada 2 — Geração automática via OpenSSL (fallback)

Se nenhuma env var está setada e não há cert cacheado em `.kalibrium/https-dev-cert/`, o script tenta rodar:

```bash
openssl req -x509 -newkey rsa:2048 -nodes -days 365 \
  -keyout .kalibrium/https-dev-cert/key.pem \
  -out .kalibrium/https-dev-cert/cert.pem \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
```

O cert é persistido em `.kalibrium/https-dev-cert/` (gitignorado) e reutilizado nas próximas execuções.

### Camada 3 — Abort com mensagem clara

Se OpenSSL não está no PATH e nenhuma env está setada, o script aborta com exit code 3 e instruções:

```
[serve-https] OpenSSL nao disponivel e KALIB_HTTPS_CERT/KEY nao setados.
  Opcoes:
   1. Instale OpenSSL no PATH.
   2. Gere cert com mkcert e exporte KALIB_HTTPS_CERT + KALIB_HTTPS_KEY.
```

## Aviso de segurança do browser

Certificados auto-assinados causam tela de aviso ("NET::ERR_CERT_AUTHORITY_INVALID" no Chrome, "SEC_ERROR_UNKNOWN_ISSUER" no Firefox). Opções:

- **Chrome / Edge:** clicar "Avançado" → "Prosseguir para localhost (inseguro)".
- **Firefox:** clicar "Avançado" → "Aceitar o risco e continuar".
- **Eliminar o aviso:** instalar `mkcert` e usar Camada 1.

Para testes E2E Playwright, usamos `ignoreHTTPSErrors: true` no `playwright.config.js`.

## Headers aplicados

| Arquivo                    | `Cache-Control`                          | Motivo                                           |
| -------------------------- | ---------------------------------------- | ------------------------------------------------ |
| `*.html`                   | `no-cache, no-store, must-revalidate`    | Evita HTML velho gruda quando atualiza shell     |
| `/sw.js`, `/registerSW.js` | `no-cache, no-store, must-revalidate`    | SW velho pode impedir ativação do novo           |
| Outros assets              | `public, max-age=31536000, immutable`    | JS/CSS/PNG com hash no nome (safe cache forever) |

Headers adicionais aplicados sempre:

- `Cross-Origin-Opener-Policy: same-origin`
- `Cross-Origin-Embedder-Policy: credentialless`

## Flags do script

```
--port <n>   porta HTTPS (default 4173)
--dir <p>    diretório estático a servir (default dist)
--host <h>   host bind (default 0.0.0.0 — aceita conexões da rede local)
```

Exemplo servindo em outra porta:

```bash
node scripts/pwa/serve-https.mjs --port 8443 --dir dist
```

## Troubleshooting

| Sintoma                                              | Causa provável                                | Fix                                                    |
| ---------------------------------------------------- | --------------------------------------------- | ------------------------------------------------------ |
| `EADDRINUSE: address already in use :::4173`         | Porta 4173 ocupada                            | `--port 4174`                                          |
| Service Worker não registra em celular Android       | Android não aceita cert auto-assinado         | Gerar cert com mkcert + instalar a CA do mkcert no celular |
| `diretorio nao encontrado: .../dist`                 | Não rodou build                               | `npm run build`                                        |
| `[serve-https] OpenSSL nao disponivel`               | OpenSSL não está no PATH                      | Instalar OpenSSL ou usar Camada 1 (mkcert)             |

## Limpeza

Para regenerar o certificado cacheado:

```bash
rm -rf .kalibrium/https-dev-cert/
npm run serve:https
```

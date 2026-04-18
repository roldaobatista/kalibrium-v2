# PWA — Geração de Ícones

Slice 017 · Operação

## O que é

O manifest do PWA (`dist/manifest.webmanifest`) declara 3 ícones obrigatórios:

| Ícone                       | Dimensão | `purpose`   | Uso                                 |
| --------------------------- | -------- | ----------- | ----------------------------------- |
| `icon-192.png`              | 192x192  | `any`       | Home screen / launcher padrão       |
| `icon-512.png`              | 512x512  | `any`       | Splash screen e stores              |
| `icon-512-maskable.png`     | 512x512  | `maskable`  | Adaptive icon (Android, Samsung)    |

Área segura do ícone maskable: **80% central** (círculo de raio 0.4 a partir do centro). O OS corta bordas em formatos variados (círculo, squircle, quadrado arredondado). Qualquer glifo fora dessa área pode ser cropado.

## Fonte única de verdade

- **SVG fonte:** `scripts/pwa/source-icon.svg` — vetorial, 512x512 viewBox.
- **Saídas geradas:** `public/icons/icon-192.png`, `public/icons/icon-512.png`, `public/icons/icon-512-maskable.png`.
- Os PNGs em `public/icons/` **são versionados no git** (commit explícito). O build do Vite copia `public/` → `dist/`; o VitePWA também referencia pelo `includeAssets`.

## Regerar os ícones

```bash
npm run generate:icons
```

O script (`scripts/pwa/generate-icons.mjs`) usa `pwa-asset-generator` com:

- input: `scripts/pwa/source-icon.svg`
- padding-body maskable: 20% (garante área segura de 80%)
- background-color: `#ffffff`
- formato: PNG (sem compressão exagerada)

O teste `tests/scaffold/pwa-icons.test.cjs` valida:

1. 3 arquivos existem em `dist/icons/` após `npm run build`.
2. Dimensões exatas (192/512/512).
3. **Pixel central (256,256) do maskable tem alpha >= 254** (AC-004-A) — garante que a área segura não foi esvaziada.

## Quando regerar

- Rebranding do logo.
- Alteração da paleta (cor de fundo, cor principal).
- Adição de uma nova densidade (`icon-180.png` iOS, por exemplo).

Qualquer mudança em `source-icon.svg` **exige** rodar `npm run generate:icons` e commitar os PNGs junto — o CI só valida dimensões, não consistência visual.

## Troubleshooting

| Sintoma                                                 | Causa provável                                              | Fix                                                    |
| ------------------------------------------------------- | ----------------------------------------------------------- | ------------------------------------------------------ |
| `pwa-asset-generator: not found`                        | devDeps não instaladas                                      | `npm install`                                          |
| Ícone maskable sem glifo central                        | SVG com glifo fora do círculo de 80%                        | Ajustar SVG para caber na área segura                  |
| Teste AC-004-A falha com alpha < 254                    | Glifo central transparente / fundo não preenche             | Usar `background-color` sólido no generate-icons.mjs   |
| Ícones em `dist/` divergem de `public/`                 | Build não rodou após gerar                                  | `npm run build`                                        |

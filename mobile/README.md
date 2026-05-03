# Kalibrium — App do Técnico (mobile/)

App mobile para técnicos de calibração. Construído com Ionic React + Capacitor.

## Pré-requisitos

-   Node.js 18 ou superior
-   Android Studio instalado (para abrir no celular Android)

## Como instalar as dependências

Execute este comando dentro da pasta `mobile/`:

```bash
npm install
```

## Como rodar no navegador (para desenvolvimento)

```bash
npm run dev
```

Abre em `http://localhost:5173`. Use o modo mobile do navegador (F12 → ícone de celular) para simular a tela do aparelho.

## Como gerar o arquivo para o Android

Primeiro gere os arquivos de produção:

```bash
npm run build
```

Depois sincronize com o projeto Android:

```bash
npx cap sync android
```

## Como abrir no Android Studio

Após o sync, execute:

```bash
npx cap open android
```

Isso abre o Android Studio com o projeto pronto. No Android Studio, conecte um celular via USB ou use o emulador e clique em "Run".

## Estrutura resumida

```
mobile/
  src/           → código-fonte React
  android/       → projeto nativo Android (gerado pelo Capacitor)
  dist/          → build gerado (não versionado)
  capacitor.config.ts → configuração do Capacitor
```

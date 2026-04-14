<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kalibrium</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <main class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center gap-6 p-6">
        <h1 class="text-3xl font-semibold">Kalibrium</h1>
        <p class="text-base">Acesso seguro do laboratorio.</p>

        <div class="flex gap-3">
            <a href="/auth/login" class="rounded bg-black px-4 py-2 text-white">Entrar</a>
            <a href="/auth/forgot-password" class="rounded border border-gray-300 px-4 py-2">Recuperar senha</a>
        </div>
    </main>
</body>
</html>

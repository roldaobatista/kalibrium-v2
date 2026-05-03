<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalibrium — Painel do Gerente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Kalibrium</h1>
            <p class="text-sm text-gray-500">Painel do gerente</p>
        </div>
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>

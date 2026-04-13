<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Kalibrium</title>
        {{-- AC-001: layout app deve carregar resources/css/app.css. --}}
        {{-- AC-001: layout app deve carregar resources/js/app.js. --}}
        {{-- AC-001: layout app deve carregar @livewireStyles. --}}
        {{-- AC-001: layout app deve carregar @livewireScripts. --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>

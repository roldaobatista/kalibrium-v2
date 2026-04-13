<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('livewire:list', function (): int {
    $classPath = config('livewire.class_path', app_path('Livewire'));
    $classNamespace = trim((string) config('livewire.class_namespace', 'App\\Livewire'), '\\');
    $normalizedClassPath = str_replace(['\\', '/'], '/', rtrim((string) $classPath, '\\/'));
    $components = [];

    if (is_dir($classPath)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($classPath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = Str::of(str_replace(['\\', '/'], '/', $file->getPathname()))
                ->after($normalizedClassPath.'/')
                ->replace('\\', '/')
                ->beforeLast('.php')
                ->explode('/');

            $name = $relativePath
                ->map(fn (string $segment): string => Str::of($segment)->kebab()->toString())
                ->implode('.');

            if (str_ends_with($name, '.index')) {
                $name = Str::beforeLast($name, '.index');
            }

            $className = $relativePath
                ->map(fn (string $segment): string => Str::of($segment)->studly()->toString())
                ->implode('\\');
            $class = $classNamespace.'\\'.$className;

            if (
                class_exists($class)
                && is_subclass_of($class, Component::class)
                && Livewire::isDiscoverable($name)
            ) {
                $components[] = $name;
            }
        }
    }

    $components = array_values(array_unique($components));
    sort($components);

    if ($components === []) {
        $this->error('Nenhum componente Livewire descoberto.');

        return 1;
    }

    foreach ($components as $component) {
        $this->line($component);
    }

    return 0;
})->purpose('Lista componentes Livewire disponiveis');

Schedule::call(function (): void {
    cache()->put('scheduler.heartbeat', now()->toIso8601String(), 120);
})->everyMinute()->name('scheduler:heartbeat')->withoutOverlapping();

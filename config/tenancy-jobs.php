<?php

declare(strict_types=1);
use App\Jobs\ProcessConsentJob;

/**
 * Lista de jobs tenant-aware do projeto Kalibrium.
 *
 * Todos os jobs listados aqui devem implementar JobTenancyBootstrapper
 * via método middleware() para garantir restauração de contexto em retries (AC-012).
 */
return [
    'tenant_aware_jobs' => [
        ProcessConsentJob::class,
    ],
];

<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Middleware\JobTenancyBootstrapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job tenant-aware para processamento de consentimentos.
 *
 * Implementa JobTenancyBootstrapper para garantir restauração de contexto
 * de tenant em retries automáticos (AC-012).
 */
final class ProcessConsentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public ?int $tenantId = null;

    public function __construct(?int $tenantId = null)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new JobTenancyBootstrapper];
    }

    public function handle(): void
    {
        // Processamento de consentimentos tenant-aware
    }
}

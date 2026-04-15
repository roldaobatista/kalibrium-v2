<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

/**
 * Contexto estático de tenant para uso em queue workers.
 *
 * Em workers de fila, request()->attributes não carrega o ciclo HTTP original.
 * Esta classe provê um canal explícito para propagar o tenant_id sem depender
 * do objeto Request — consumido por ScopesToCurrentTenant e JobTenancyBootstrapper.
 */
final class TenantContext
{
    private static ?int $currentTenantId = null;

    public static function setTenantId(?int $id): void
    {
        self::$currentTenantId = $id;
    }

    public static function getTenantId(): ?int
    {
        return self::$currentTenantId;
    }

    public static function reset(): void
    {
        self::$currentTenantId = null;
    }
}

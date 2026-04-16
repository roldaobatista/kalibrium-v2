<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

/**
 * Flag global de bypass do fail-closed em ScopesToCurrentTenant.
 *
 * Usada exclusivamente por SetCurrentTenantContext durante o bootstrap do
 * contexto de tenant (antes de request()->attributes ser populado).
 * Evita que queries de resolução do próprio tenant sejam bloqueadas pelo scope. (SEC-001)
 */
final class TenantScopeBypass
{
    private static bool $active = false;

    public static function isActive(): bool
    {
        return self::$active;
    }

    /**
     * Executa o callback com bypass ativo e restaura o estado ao terminar.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        self::$active = true;
        try {
            return $callback();
        } finally {
            self::$active = false;
        }
    }
}

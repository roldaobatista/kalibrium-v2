<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

final class TenantRole
{
    public const string MANAGER = 'gerente';

    public const string TECHNICIAN = 'tecnico';

    public const string ADMINISTRATIVE = 'administrativo';

    public const string VIEWER = 'visualizador';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            self::MANAGER,
            self::TECHNICIAN,
            self::ADMINISTRATIVE,
            self::VIEWER,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function critical(): array
    {
        return [
            self::MANAGER,
            self::ADMINISTRATIVE,
        ];
    }

    public static function requiresTwoFactor(string $role): bool
    {
        return in_array(strtolower($role), self::critical(), true);
    }

    public static function canManageUsers(string $role): bool
    {
        return strtolower($role) === self::MANAGER;
    }

    public static function canViewPlan(string $role): bool
    {
        return in_array(strtolower($role), [
            self::MANAGER,
            self::ADMINISTRATIVE,
            self::VIEWER,
        ], true);
    }

    public static function canRequestPlanUpgrade(string $role): bool
    {
        return strtolower($role) === self::MANAGER;
    }
}

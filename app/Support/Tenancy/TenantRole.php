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
     * Operational role used in contatos API contract.
     * Maps to write-capable roles for contatos and clientes resources.
     */
    public const string ATTENDANT = 'atendente';

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
            self::ATTENDANT,
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

    /**
     * Roles allowed to manage clientes (create, deactivate).
     * Spec role "atendente" maps to operational roles: gerente, tecnico, administrativo.
     */
    public static function canManageClientes(string $role): bool
    {
        return in_array(strtolower($role), [
            self::MANAGER,
            self::TECHNICIAN,
            self::ADMINISTRATIVE,
        ], true);
    }

    /**
     * Roles allowed to READ clientes (index, show).
     * Includes: gerente, tecnico, administrativo, visualizador.
     */
    public static function canReadClientes(string $role): bool
    {
        return in_array(strtolower($role), [
            self::MANAGER,
            self::TECHNICIAN,
            self::ADMINISTRATIVE,
            self::VIEWER,
        ], true);
    }

    /**
     * Roles allowed to WRITE clientes (create, update, deactivate).
     * Excludes tecnico and visualizador — write requires gerente or administrativo.
     */
    public static function canWriteClientes(string $role): bool
    {
        return in_array(strtolower($role), [
            self::MANAGER,
            self::ADMINISTRATIVE,
        ], true);
    }

    /**
     * Roles allowed to READ contatos (index, show).
     * Includes: gerente, tecnico, administrativo, visualizador, atendente.
     * API contract role "atendente" maps to full read+write access on contatos.
     */
    public static function canReadContatos(string $role): bool
    {
        return in_array(strtolower($role), [
            self::MANAGER,
            self::TECHNICIAN,
            self::ADMINISTRATIVE,
            self::VIEWER,
            self::ATTENDANT,
        ], true);
    }

    /**
     * Roles allowed to WRITE contatos (create, update, deactivate).
     * Excludes tecnico and visualizador — write requires gerente, administrativo or atendente.
     * API contract role "atendente" maps to full read+write access on contatos.
     */
    public static function canWriteContatos(string $role): bool
    {
        return in_array(strtolower($role), [
            self::MANAGER,
            self::ADMINISTRATIVE,
            self::ATTENDANT,
        ], true);
    }
}

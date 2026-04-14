<?php

declare(strict_types=1);

use App\Support\Settings\UserRoleService;
use Illuminate\Auth\Access\AuthorizationException;

require_once __DIR__.'/TestHelpers.php';

test('AC-004: gerente altera papel de usuario do mesmo tenant, recalcula 2FA para papel critico e registra auditoria', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $member = slice009_create_tenant_member($context, [
        'role' => 'tecnico',
        'status' => 'active',
    ]);

    app(UserRoleService::class)->updateRole(
        $context['user'],
        $context['tenant_user'],
        $member['tenant_user'],
        'administrativo',
    );

    $member['tenant_user']->refresh();
    expect($member['tenant_user']->role)->toBe('administrativo');
    expect((bool) $member['tenant_user']->requires_2fa)->toBeTrue();
    slice009_assert_audit_does_not_leak($context['tenant']->id);
})->group('slice-009', 'ac-004');

test('AC-012: ultimo gerente ativo nao pode ser rebaixado para outro papel', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);

    expect(fn () => app(UserRoleService::class)->updateRole(
        $context['user'],
        $context['tenant_user'],
        $context['tenant_user'],
        'tecnico',
    ))->toThrow(AuthorizationException::class);

    $context['tenant_user']->refresh();
    expect($context['tenant_user']->role)->toBe('gerente');
    expect($context['tenant_user']->status)->toBe('active');
})->group('slice-009', 'ac-012');

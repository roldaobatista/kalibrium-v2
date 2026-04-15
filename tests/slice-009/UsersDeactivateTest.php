<?php

declare(strict_types=1);

use App\Support\Auth\TenantAccessResolver;
use App\Support\Settings\UserDeactivationService;
use Illuminate\Auth\Access\AuthorizationException;

require_once __DIR__.'/TestHelpers.php';

test('AC-005: gerente desativa usuario do mesmo tenant sem remover o ultimo gerente e registra auditoria', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $member = slice009_create_tenant_member($context, [
        'role' => 'tecnico',
        'status' => 'active',
    ]);

    app(UserDeactivationService::class)->deactivate(
        $context['user'],
        $context['tenant_user'],
        $member['tenant_user'],
    );

    $member['tenant_user']->refresh();
    expect(in_array($member['tenant_user']->status, ['removed', 'suspended'], true))->toBeTrue();

    $decision = app(TenantAccessResolver::class)->resolve($member['user']->fresh());
    expect($decision['allowed'])->toBeFalse();
    expect($decision['event'])->toBe('auth.login.blocked_binding_status');
    expect($decision['tenant_user_id'])->toBe($member['tenant_user']->id);
    slice009_assert_audit_does_not_leak($context['tenant']->id);
})->group('slice-009', 'ac-005');

test('AC-012: ultimo gerente ativo nao pode remover a si mesmo nem deixar o tenant sem gerente', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);

    expect(fn () => app(UserDeactivationService::class)->deactivate(
        $context['user'],
        $context['tenant_user'],
        $context['tenant_user'],
    ))->toThrow(AuthorizationException::class);

    $context['tenant_user']->refresh();
    expect($context['tenant_user']->role)->toBe('gerente');
    expect($context['tenant_user']->status)->toBe('active');
})->group('slice-009', 'ac-012');

test('AC-005: gerente pode remover seu proprio acesso quando outro gerente ativo permanece', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $otherManager = slice009_create_tenant_member($context, [
        'role' => 'gerente',
        'status' => 'active',
    ]);

    app(UserDeactivationService::class)->deactivate(
        $context['user'],
        $context['tenant_user'],
        $context['tenant_user'],
    );

    $context['tenant_user']->refresh();
    $otherManager['tenant_user']->refresh();

    expect($context['tenant_user']->status)->toBe('removed');
    expect($otherManager['tenant_user']->role)->toBe('gerente');
    expect($otherManager['tenant_user']->status)->toBe('active');
})->group('slice-009', 'ac-005');

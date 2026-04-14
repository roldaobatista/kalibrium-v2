<?php

declare(strict_types=1);

use App\Mail\UserInvitationMail;
use App\Models\Branch;
use App\Models\Company;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\UserInvitationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/TestHelpers.php';

test('AC-002: gerente convida usuario, vinculo pendente fica no tenant atual, 2FA e obrigatoria para papeis criticos e auditoria nao vaza token', function (): void {
    Mail::fake();
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $payload = slice009_invite_payload($context, [
        'role' => 'gerente',
        'email' => slice009_unique_email(),
    ]);

    app(UserInvitationService::class)->invite($context['user'], $context['tenant_user'], $payload);

    $invitedUser = User::query()->where('email', mb_strtolower($payload['email']))->first();
    expect($invitedUser)->not->toBeNull();

    $tenantUser = TenantUser::query()
        ->where('tenant_id', $context['tenant']->id)
        ->where('user_id', $invitedUser->id)
        ->first();

    expect($tenantUser)->not->toBeNull();
    expect($tenantUser->status)->toBe('invited');
    expect($tenantUser->role)->toBe('gerente');
    expect((bool) $tenantUser->requires_2fa)->toBeTrue();

    $invitedUser->forceFill([
        'password' => Hash::make('SenhaConviteGerente123!'),
        'two_factor_confirmed_at' => null,
    ])->save();
    $tenantUser->forceFill(['status' => 'active'])->save();

    $login = $this->post('/auth/login', [
        'email' => $invitedUser->email,
        'password' => 'SenhaConviteGerente123!',
    ]);
    $login->assertRedirect('/auth/two-factor-challenge');
    $login->assertSessionHas('auth.two_factor_pending', true);

    $privilegedAccess = $this->get(slice009_routes()['users']);
    $privilegedAccess->assertRedirect('/auth/two-factor-challenge');

    Mail::assertSentCount(1);
    Mail::assertSent(UserInvitationMail::class, static function (UserInvitationMail $mail): bool {
        return str_contains($mail->invitationUrl, '/auth/invitations/');
    });
    slice009_assert_audit_does_not_leak($context['tenant']->id, [
        'SenhaSegura123!',
        'invitation_token_hash',
        (string) ($tenantUser->invitation_token_hash ?? ''),
    ]);
})->group('slice-009', 'ac-002');

test('AC-010: convite com campos invalidos ou empresa e filial de outro tenant retorna erro e nao cria vinculo', function (array $payloadOverrides): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $external = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $beforeUsers = User::query()->count();
    $beforeTenantUsers = TenantUser::query()->where('tenant_id', $context['tenant']->id)->count();
    $payload = slice009_invite_payload($context, array_merge([
        'company_id' => $context['company']->id,
        'branch_id' => $context['branch']->id,
    ], $payloadOverrides));

    if (($payloadOverrides['company_id'] ?? null) === 'external') {
        $payload['company_id'] = $external['company']->id;
    }
    if (($payloadOverrides['branch_id'] ?? null) === 'external') {
        $payload['branch_id'] = $external['branch']->id;
    }

    expect(fn () => app(UserInvitationService::class)
        ->invite($context['user'], $context['tenant_user'], $payload))->toThrow(ValidationException::class);

    expect(User::query()->count())->toBe($beforeUsers);
    expect(TenantUser::query()->where('tenant_id', $context['tenant']->id)->count())->toBe($beforeTenantUsers);
    slice009_assert_audit_does_not_leak($context['tenant']->id, [$payload['email'] ?? '']);
})->with([
    'nome vazio' => [['name' => '']],
    'email invalido' => [['email' => 'email-invalido']],
    'papel invalido' => [['role' => 'owner']],
    'empresa de outro tenant' => [['company_id' => 'external']],
    'filial de outro tenant' => [['branch_id' => 'external']],
])->group('slice-009', 'ac-010');

test('AC-010: convite bloqueia filial que nao pertence a empresa informada no mesmo tenant', function (): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $otherCompany = Company::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'legal_name' => 'Outra empresa '.Str::uuid(),
    ]);
    $otherBranch = Branch::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'company_id' => $otherCompany->id,
        'name' => 'Outra filial '.Str::uuid(),
    ]);

    expect(fn () => app(UserInvitationService::class)->invite(
        $context['user'],
        $context['tenant_user'],
        slice009_invite_payload($context, [
            'company_id' => $context['company']->id,
            'branch_id' => $otherBranch->id,
        ]),
    ))->toThrow(ValidationException::class);
})->group('slice-009', 'ac-010');

test('AC-011: e-mail ja ativo ou convidado no tenant atual nao recebe segundo convite', function (string $existingStatus): void {
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $member = slice009_create_tenant_member($context, [
        'status' => $existingStatus,
        'email' => slice009_unique_email(),
    ]);
    $before = TenantUser::query()
        ->where('tenant_id', $context['tenant']->id)
        ->where('user_id', $member['user']->id)
        ->count();
    $payload = slice009_invite_payload($context, [
        'email' => $member['user']->email,
        'role' => 'tecnico',
    ]);

    expect(fn () => app(UserInvitationService::class)
        ->invite($context['user'], $context['tenant_user'], $payload))->toThrow(ValidationException::class);

    expect(TenantUser::query()
        ->where('tenant_id', $context['tenant']->id)
        ->where('user_id', $member['user']->id)
        ->count())->toBe($before);
})->with([
    'ativo' => ['active'],
    'convidado' => ['invited'],
])->group('slice-009', 'ac-011');

test('AC-013: convite para usuario existente em outro tenant nao altera nome global da conta', function (): void {
    Mail::fake();
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $external = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'tecnico',
        'user_name' => 'Nome Original Externo',
    ]);

    app(UserInvitationService::class)->invite($context['user'], $context['tenant_user'], slice009_invite_payload($context, [
        'name' => 'Nome Tentativa Alteracao',
        'email' => $external['user']->email,
        'role' => 'visualizador',
    ]));

    expect($external['user']->fresh()->name)->toBe('Nome Original Externo');
    expect(TenantUser::query()
        ->where('tenant_id', $context['tenant']->id)
        ->where('user_id', $external['user']->id)
        ->where('status', 'invited')
        ->exists())->toBeTrue();
})->group('slice-009', 'ac-013');

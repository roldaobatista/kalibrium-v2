<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Mail\UserInvitationMail;
use App\Models\Branch;
use App\Models\Company;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\Concerns\AuthorizesTenantSettings;
use App\Support\Tenancy\TenantAuditRecorder;
use App\Support\Tenancy\TenantRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class UserInvitationService
{
    use AuthorizesTenantSettings;

    public function __construct(
        private TenantAuditRecorder $auditRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws AuthorizationException|ValidationException
     */
    public function invite(User $actor, TenantUser $actorTenantUser, array $payload): TenantUser
    {
        $tenant = $this->assertActiveManager($actor, $actorTenantUser);
        $data = $this->validatedInvitation($payload, (int) $tenant->id);

        /** @var array{tenant_user: TenantUser, email: string, invitation_url: string} $invitation */
        $invitation = DB::transaction(function () use ($actor, $data, $tenant) {
            $email = mb_strtolower(trim((string) $data['email']));
            $role = strtolower((string) $data['role']);
            $existingUser = User::query()->where('email', $email)->first();

            if ($existingUser !== null && TenantUser::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $existingUser->id)
                ->whereIn('status', ['active', 'invited'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Este e-mail ja possui acesso ou convite pendente neste laboratorio.',
                ]);
            }

            $user = $existingUser ?? User::query()->create([
                'name' => trim((string) $data['name']),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => null,
            ]);

            $token = Str::random(64);
            try {
                $tenantUser = TenantUser::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'company_id' => $data['company_id'] ?? null,
                    'branch_id' => $data['branch_id'] ?? null,
                    'role' => $role,
                    'status' => 'invited',
                    'requires_2fa' => TenantRole::requiresTwoFactor($role),
                    'invited_at' => now(),
                    'accepted_at' => null,
                    'invitation_token_hash' => hash('sha256', $token),
                    'invitation_expires_at' => now()->addDays(7),
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueConstraintViolation($exception)) {
                    throw ValidationException::withMessages([
                        'email' => 'Este e-mail ja possui acesso ou convite pendente neste laboratorio.',
                    ]);
                }

                throw $exception;
            }

            $this->auditRecorder->record(
                request(),
                (int) $tenant->id,
                $actor->id,
                'tenant.user.invited',
                ['name', 'email', 'role', 'company_id', 'branch_id', 'requires_2fa'],
            );

            return [
                'tenant_user' => $tenantUser,
                'email' => $email,
                'invitation_url' => (string) route('auth.invitations.accept', ['token' => $token]),
            ];
        });

        try {
            Mail::to($invitation['email'])->send(new UserInvitationMail($invitation['invitation_url']));
        } catch (Throwable $exception) {
            $this->rollbackUndeliveredInvitation((int) $invitation['tenant_user']->id);

            throw $exception;
        }

        return $invitation['tenant_user'];
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public function accept(string $token, array $payload): TenantUser
    {
        $data = Validator::make($payload, [
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ])->validate();

        $tenantUser = $this->tenantUserForToken($token);
        if ($tenantUser === null) {
            throw ValidationException::withMessages([
                'token' => 'Convite invalido ou expirado. Solicite novo convite.',
            ]);
        }

        return DB::transaction(function () use ($tenantUser, $data): TenantUser {
            $fresh = TenantUser::query()
                ->with('user')
                ->whereKey($tenantUser->id)
                ->where('status', 'invited')
                ->lockForUpdate()
                ->first();
            if ($fresh === null || $fresh->user === null) {
                throw ValidationException::withMessages([
                    'token' => 'Convite invalido ou expirado. Solicite novo convite.',
                ]);
            }
            $user = $fresh->user;

            if (! $this->hasActiveAccessOutsideInvitation($user, $fresh)) {
                $user->forceFill([
                    'password' => Hash::make((string) $data['password']),
                ])->save();
            }

            $fresh->forceFill([
                'status' => 'active',
                'accepted_at' => now(),
                'invitation_token_hash' => null,
                'invitation_expires_at' => null,
            ])->save();

            $this->auditRecorder->record(
                request(),
                (int) $fresh->tenant_id,
                $user->id,
                'tenant.user.invitation.accepted',
                ['status', 'accepted_at'],
            );

            return $fresh;
        });
    }

    public function tenantUserForToken(string $token): ?TenantUser
    {
        if (trim($token) === '') {
            return null;
        }

        return TenantUser::query()
            ->with('user')
            ->where('invitation_token_hash', hash('sha256', $token))
            ->where('status', 'invited')
            ->where(static function ($query): void {
                $query->whereNull('invitation_expires_at')
                    ->orWhere('invitation_expires_at', '>=', now());
            })
            ->first();
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $message = strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, ['1062', '1555', '2067'], true)
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }

    private function rollbackUndeliveredInvitation(int $tenantUserId): void
    {
        DB::transaction(function () use ($tenantUserId): void {
            TenantUser::query()
                ->whereKey($tenantUserId)
                ->where('status', 'invited')
                ->update([
                    'status' => 'removed',
                    'invitation_token_hash' => null,
                    'invitation_expires_at' => null,
                ]);
        });
    }

    private function hasActiveAccessOutsideInvitation(User $user, TenantUser $invitation): bool
    {
        return TenantUser::query()
            ->where('user_id', $user->id)
            ->whereKeyNot($invitation->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{name:string,email:string,role:string,company_id?:int|null,branch_id?:int|null}
     *
     * @throws ValidationException
     */
    private function validatedInvitation(array $payload, int $tenantId): array
    {
        $data = Validator::make($payload, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', 'string', Rule::in(TenantRole::values())],
            'company_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ])->validate();

        if (isset($data['company_id']) && ! Company::query()
            ->whereKey($data['company_id'])
            ->where('tenant_id', $tenantId)
            ->exists()) {
            throw ValidationException::withMessages(['company_id' => 'Empresa invalida.']);
        }

        if (isset($data['branch_id']) && ! isset($data['company_id'])) {
            throw ValidationException::withMessages(['company_id' => 'Empresa obrigatoria para a filial informada.']);
        }

        if (isset($data['branch_id']) && ! Branch::query()
            ->whereKey($data['branch_id'])
            ->where('tenant_id', $tenantId)
            ->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'Filial invalida.']);
        }

        if (isset($data['company_id'], $data['branch_id']) && ! Branch::query()
            ->whereKey($data['branch_id'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $data['company_id'])
            ->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'Filial invalida para a empresa informada.']);
        }

        return [
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'role' => (string) $data['role'],
            'company_id' => isset($data['company_id']) ? (int) $data['company_id'] : null,
            'branch_id' => isset($data['branch_id']) ? (int) $data['branch_id'] : null,
        ];
    }
}

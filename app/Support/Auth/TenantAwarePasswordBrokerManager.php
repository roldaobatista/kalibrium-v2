<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Gerenciador de broker de senha ciente de tenant.
 *
 * Substitui o DatabaseTokenRepository padrão pelo TenantAwarePasswordTokenRepository,
 * que inclui tenant_id em todas as operações de criação/busca de tokens.
 */
final class TenantAwarePasswordBrokerManager extends PasswordBrokerManager
{
    /**
     * @param  array<string, mixed>  $config
     */
    protected function createTokenRepository(array $config): TenantAwarePasswordTokenRepository
    {
        /** @var ConfigRepository $configRepo */
        $configRepo = $this->app->make(ConfigRepository::class);
        $key = (string) $configRepo->get('app.key', '');

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        /** @var ConnectionResolverInterface $db */
        $db = $this->app->make(ConnectionResolverInterface::class);

        /** @var Hasher $hasher */
        $hasher = $this->app->make(Hasher::class);

        return new TenantAwarePasswordTokenRepository(
            $db->connection($config['connection'] ?? null),
            $hasher,
            (string) ($config['table'] ?? 'password_reset_tokens'),
            $key,
            (int) (($config['expire'] ?? 60) * 60),
            (int) ($config['throttle'] ?? 0),
        );
    }
}

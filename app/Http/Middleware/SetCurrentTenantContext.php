<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Auth\PostgresAuthContext;
use App\Support\Tenancy\CurrentTenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetCurrentTenantContext
{
    public function __construct(
        private CurrentTenantResolver $resolver,
        private PostgresAuthContext $postgresAuthContext,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->postgresAuthContext->forUser($user->id);
        $context = $this->resolver->resolve($user);
        $this->postgresAuthContext->forTenant($context['tenant']->id);

        $request->attributes->set('current_tenant', $context['tenant']);
        $request->attributes->set('current_tenant_user', $context['tenant_user']);
        $request->session()->put('tenant.access_mode', $context['access_mode']);

        return $next($request);
    }
}

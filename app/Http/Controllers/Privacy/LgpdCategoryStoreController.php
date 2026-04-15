<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Lgpd\LgpdCategoryService;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class LgpdCategoryStoreController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentTenantResolver $resolver,
        LgpdCategoryService $service,
    ): RedirectResponse {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $context = $resolver->resolve($user);

        try {
            $service->declare($context['tenant'], $user, $request->all());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->back()->with('status', 'Base legal registrada.');
    }
}

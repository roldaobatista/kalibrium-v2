<?php

declare(strict_types=1);

// routes/dev.php — rotas exclusivas de desenvolvimento local.
//
// ATENÇÃO: Este arquivo SÓ é carregado quando:
//   - APP_ENV=local  E
//   - APP_ENABLE_TEST_LOGIN=true  (deve estar explícito no .env local)
//
// NUNCA deve existir em staging, homologação ou produção.
// Qualquer rota aqui é um acesso sem senha — risco crítico em ambiente errado.

use App\Models\User;
use Illuminate\Support\Facades\Route;

// Rota de auto-login para aceite visual (e2e-aceite / Playwright).
// Permite logar como qualquer usuário pelo ID sem precisar de senha.
// Exige APP_ENABLE_TEST_LOGIN=true além de APP_ENV=local como barreira dupla.
Route::get('/aceite-login/{userId}', function (int $userId) {
    $user = User::findOrFail($userId);
    auth()->login($user);

    return redirect('/mobile-devices');
})->name('aceite.login');

<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

require_once __DIR__.'/TestHelpers.php';

test('AC-005: POST /auth/forgot-password responde com mensagem neutra para e-mail valido', function (): void {
    Mail::fake();

    $response = $this->postJson(slice007_routes()['forgot_password'], slice007_forgot_password_payload());

    $response->assertStatus(302);
    $response->assertSessionHas('status', 'Se o e-mail existir, enviaremos um link.');
    Mail::assertNothingSent();
})->group('slice-007', 'ac-005');

test('AC-005: POST /auth/forgot-password para usuario existente envia link sem revelar existencia da conta', function (): void {
    Notification::fake();
    $user = slice007_persisted_user([
        'email' => slice007_unique_email(),
    ]);

    $response = $this->postJson(slice007_routes()['forgot_password'], slice007_forgot_password_payload([
        'email' => $user->email,
    ]));

    $response->assertStatus(302);
    $response->assertSessionHas('status', 'Se o e-mail existir, enviaremos um link.');
    Notification::assertSentTo($user, ResetPasswordNotification::class);
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->email,
    ]);
})->group('slice-007', 'ac-005');

test('AC-005: tela de recuperar senha renderiza mensagem neutra de envio', function (): void {
    $response = $this
        ->from(slice007_routes()['forgot_password'])
        ->post(slice007_routes()['forgot_password'], slice007_forgot_password_payload());

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['forgot_password']);
    $response->assertSessionHas('status', 'Se o e-mail existir, enviaremos um link.');

    $page = $this->get(slice007_routes()['forgot_password']);

    $page->assertStatus(200);
    $page->assertSee('Se o e-mail existir, enviaremos um link.');
})->group('slice-007', 'ac-005');

test('AC-010: POST /auth/forgot-password com e-mail invalido retorna 422 e nao envia e-mail', function (): void {
    Mail::fake();

    $response = $this->postJson(slice007_routes()['forgot_password'], [
        'email' => 'email-invalido',
    ]);

    $response->assertStatus(422);
    Mail::assertNothingSent();
})->group('slice-007', 'ac-010');

test('AC-006: POST /auth/reset-password com token valido altera a senha, invalida o token e redireciona para /auth/login', function (): void {
    $user = slice007_persisted_user([
        'email' => slice007_unique_email(),
        'password' => Hash::make('SenhaAtual123!'),
        'remember_token' => 'remember-token-before-reset',
    ]);
    $token = slice007_reset_password_token_for($user);

    $response = $this->withSession(['auth.password_reset_token' => $token])->postJson(slice007_routes()['reset_password'], slice007_reset_password_payload(
        null,
        $user->email
    ));

    $response->assertStatus(302);
    $response->assertRedirect(slice007_routes()['login']);

    expect(Hash::check('NovaSenhaSegura123!', $user->fresh()->password))->toBeTrue(
        'AC-006: a senha deve ser atualizada após reset bem-sucedido.'
    );
    expect($user->fresh()->remember_token)->not->toBe('remember-token-before-reset');
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => $user->email,
    ]);
})->group('slice-007', 'ac-006');

test('AC-006: pagina de reset guarda o token na sessao sem renderizar no HTML', function (): void {
    $user = slice007_persisted_user([
        'email' => slice007_unique_email(),
    ]);
    $token = slice007_reset_password_token_for($user);

    $response = $this->get('/auth/reset-password/'.$token);

    $response->assertStatus(302);
    $response->assertRedirect('/auth/reset-password');
    $response->assertSessionHas('auth.password_reset_token', $token);
    $page = $this->get('/auth/reset-password');

    $page->assertStatus(200);
    expect((string) $page->getContent())->not->toContain($token);
})->group('slice-007', 'ac-006', 'security');

test('AC-016: POST /auth/reset-password com senha fraca ou confirmacao divergente retorna 422 e preserva a senha atual', function (): void {
    $user = slice007_persisted_user([
        'email' => slice007_unique_email(),
        'password' => Hash::make('SenhaAtual123!'),
    ]);
    $token = slice007_reset_password_token_for($user);

    $response = $this->postJson(slice007_routes()['reset_password'], slice007_reset_password_payload(
        $token,
        $user->email,
        [
            'password' => 'Curta1!',
            'password_confirmation' => 'Curta1!',
        ]
    ));

    $response->assertStatus(422);
    expect(Hash::check('SenhaAtual123!', $user->fresh()->password))->toBeTrue(
        'AC-016: a senha atual nao deve mudar quando a nova senha nao atende a regra.'
    );
})->group('slice-007', 'ac-016');

test('AC-016: POST /auth/reset-password com confirmacao divergente retorna 422 e preserva a senha atual', function (): void {
    $user = slice007_persisted_user([
        'email' => slice007_unique_email(),
        'password' => Hash::make('SenhaAtual123!'),
    ]);
    $token = slice007_reset_password_token_for($user);

    $response = $this->withSession(['auth.password_reset_token' => $token])->postJson(slice007_routes()['reset_password'], slice007_reset_password_payload(
        null,
        $user->email,
        [
            'password' => 'NovaSenhaSegura123!',
            'password_confirmation' => 'OutraSenhaSegura123!',
        ]
    ));

    $response->assertStatus(422);
    expect(Hash::check('SenhaAtual123!', $user->fresh()->password))->toBeTrue(
        'AC-016: a senha atual nao deve mudar quando a confirmacao diverge.'
    );
})->group('slice-007', 'ac-016');

test('AC-017: POST /auth/reset-password com token invalido ou expirado retorna 422 e orienta novo link', function (): void {
    $user = slice007_persisted_user([
        'email' => slice007_unique_email(),
        'password' => Hash::make('SenhaAtual123!'),
    ]);

    $response = $this->postJson(slice007_routes()['reset_password'], slice007_reset_password_payload(
        'token-invalido',
        $user->email
    ));

    $response->assertStatus(422);
    expect(Hash::check('SenhaAtual123!', $user->fresh()->password))->toBeTrue(
        'AC-017: a senha atual nao deve mudar quando o token e invalido.'
    );
    slice007_assert_body_does_not_leak_secrets($response, [
        'token-invalido',
    ]);
})->group('slice-007', 'ac-017');

test('AC-017: POST /auth/reset-password com token expirado retorna 422 e preserva a senha atual', function (): void {
    $user = slice007_persisted_user([
        'email' => slice007_unique_email(),
        'password' => Hash::make('SenhaAtual123!'),
    ]);
    $token = slice007_reset_password_token_for($user);
    DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->update(['created_at' => now()->subMinutes((int) config('auth.passwords.users.expire') + 5)]);

    $response = $this->withSession(['auth.password_reset_token' => $token])->postJson(slice007_routes()['reset_password'], slice007_reset_password_payload(
        null,
        $user->email
    ));

    $response->assertStatus(422);
    expect(Hash::check('SenhaAtual123!', $user->fresh()->password))->toBeTrue(
        'AC-017: a senha atual nao deve mudar quando o token expirou.'
    );
    slice007_assert_body_does_not_leak_secrets($response, [
        $token,
    ]);
})->group('slice-007', 'ac-017');

test('AC-017: formulario HTML de reset com token invalido redireciona com erro de sessao', function (): void {
    $user = slice007_persisted_user([
        'email' => slice007_unique_email(),
        'password' => Hash::make('SenhaAtual123!'),
    ]);

    $response = $this
        ->from('/auth/reset-password')
        ->withSession(['auth.password_reset_token' => 'token-invalido'])
        ->post(slice007_routes()['reset_password'], slice007_reset_password_payload(
            null,
            $user->email
        ));

    $response->assertStatus(302);
    $response->assertRedirect('/auth/reset-password');
    $response->assertSessionHasErrors('token');
    $page = $this->get('/auth/reset-password');

    $page->assertStatus(200);
    $page->assertSee('Token invalido ou expirado. Solicite novo link.');
    expect(Hash::check('SenhaAtual123!', $user->fresh()->password))->toBeTrue();
})->group('slice-007', 'ac-017');

<?php

declare(strict_types=1);

namespace App\Http\Responses\Auth;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthFailureResponse
{
    public static function make(
        Request $request,
        string $message,
        int $status,
        string $field = 'email',
    ): Response {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
            ], $status);
        }

        return back()
            ->with('auth_error_messages', [$message])
            ->withErrors([$field => $message])
            ->withInput($request->except([
                'password',
                'password_confirmation',
                'token',
                'code',
                'recovery_code',
            ]));
    }
}

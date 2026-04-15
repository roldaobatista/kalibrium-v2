<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Models\ConsentRecord;
use App\Services\ConsentRecordService;
use App\Services\RevocationTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

final class RevocationSubmitController
{
    public function __invoke(
        Request $request,
        string $token,
        RevocationTokenService $tokenService,
        ConsentRecordService $consentService,
    ): Response {
        $outcome = $tokenService->processRevocationAttempt($token);

        if ($outcome['status'] === 'renewed') {
            $tokenService->dispatchRenewalLink($outcome);

            return response('Link expirado. Solicite um novo link de revogação.', 200);
        }

        if ($outcome['status'] === 'not_found') {
            abort(404);
        }

        $data = $request->validate([
            'revocation_reason' => ['nullable', Rule::in(ConsentRecord::REVOCATION_REASONS)],
        ]);

        $reason = (string) ($data['revocation_reason'] ?? 'other_without_details');

        $record = $tokenService->finalizeRevocation(
            $consentService,
            $outcome['token'],
            $reason,
            ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent() ?? '']
        );

        if ($record === null) {
            return response('Voce nao tem consentimento ativo para este canal', 200);
        }

        return response('Consentimento revogado com sucesso.', 200);
    }
}

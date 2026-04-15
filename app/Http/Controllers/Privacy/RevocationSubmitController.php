<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Mail\RevocationConfirmationMail;
use App\Mail\RevocationLinkMail;
use App\Services\ConsentRecordService;
use App\Services\RevocationTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

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
            Mail::send(new RevocationLinkMail(
                $outcome['subject'],
                $outcome['channel'],
                $outcome['rawToken']
            ));

            return response('Link expirado. Solicite um novo link de revogação.', 200);
        }

        if ($outcome['status'] === 'not_found') {
            abort(404);
        }

        $validToken = $outcome['token'];
        $subject = $validToken->consentSubject;
        $channel = $validToken->channel;
        $reason = (string) $request->input('revocation_reason', 'other_without_details');

        $record = $consentService->revokeConsent(
            (int) $validToken->tenant_id,
            (int) $validToken->consent_subject_id,
            $channel,
            $reason,
            ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent() ?? '']
        );

        if ($record === null) {
            return response('Voce nao tem consentimento ativo para este canal', 200);
        }

        $tokenService->consume($validToken);

        if ($subject !== null && $subject->email !== null && $subject->email !== '') {
            Mail::send(new RevocationConfirmationMail(
                $subject,
                $channel,
                now()
            ));
        }

        return response('Consentimento revogado com sucesso.', 200);
    }
}

/**
 * Serviço de autenticação mobile.
 *
 * Comunica com POST /api/mobile/login no backend Laravel.
 * tenant_id está fixo em VITE_TENANT_ID por enquanto —
 * a escolha de tenant pelo técnico será história futura.
 */

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL as string;
const TENANT_ID = Number(import.meta.env.VITE_TENANT_ID ?? 1);

// ---------------------------------------------------------------------------
// Tipos de retorno
// ---------------------------------------------------------------------------

export interface LoginOk {
    kind: 'ok';
    token: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
}

export interface LoginPending {
    kind: 'pending';
    message: string;
}

export interface LoginRevoked {
    kind: 'revoked';
    message: string;
}

export interface LoginUnauthorized {
    kind: 'unauthorized';
    message: string;
}

export interface LoginValidationError {
    kind: 'validation';
    message: string;
}

export interface LoginRateLimit {
    kind: 'rate_limit';
    message: string;
}

export interface LoginNetworkError {
    kind: 'network_error';
}

export type LoginResult =
    | LoginOk
    | LoginPending
    | LoginRevoked
    | LoginUnauthorized
    | LoginValidationError
    | LoginRateLimit
    | LoginNetworkError;

// ---------------------------------------------------------------------------
// Função principal
// ---------------------------------------------------------------------------

export async function login(
    email: string,
    password: string,
    deviceIdentifier: string,
    deviceLabel: string,
): Promise<LoginResult> {
    let response: Response;

    try {
        response = await fetch(`${API_BASE_URL}/api/mobile/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                email,
                password,
                device_identifier: deviceIdentifier,
                device_label: deviceLabel,
                tenant_id: TENANT_ID,
            }),
        });
    } catch {
        return { kind: 'network_error' };
    }

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    let body: any = {};
    try {
        body = await response.json();
    } catch {
        // corpo vazio ou inválido — continua com objeto vazio
    }

    switch (response.status) {
        case 200:
            return {
                kind: 'ok',
                token: body.token as string,
                user: body.user as LoginOk['user'],
            };

        case 202:
            return {
                kind: 'pending',
                message: (body.message as string) ?? 'Aguardando aprovação do gerente.',
            };

        case 403:
            return {
                kind: 'revoked',
                message: (body.message as string) ?? 'Acesso revogado.',
            };

        case 401:
            return {
                kind: 'unauthorized',
                message: (body.message as string) ?? 'E-mail ou senha incorretos.',
            };

        case 422:
            return {
                kind: 'validation',
                message: (body.message as string) ?? 'Verifique os dados e tente de novo.',
            };

        case 429:
            return {
                kind: 'rate_limit',
                message:
                    (body.message as string) ??
                    'Muitas tentativas. Aguarde alguns minutos e tente de novo.',
            };

        default:
            return {
                kind: 'unauthorized',
                message: 'Erro inesperado. Tente de novo.',
            };
    }
}

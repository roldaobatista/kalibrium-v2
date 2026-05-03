/**
 * Wrapper de fetch autenticado para o backend Kalibrium.
 *
 * Adiciona automaticamente:
 * - Authorization: Bearer {token}
 * - X-Device-Id: {deviceIdentifier}
 *
 * Reage ao sinal de wipe (401 + body.wipe === true):
 * - Limpa todos os dados locais (banco criptografado + biometria + preferências)
 * - Redireciona para /blocked
 *
 * Reage a 401 normal:
 * - Remove token e redireciona para /login
 */

import * as biometric from './biometric';
import { getDeviceIdentifier } from './device';
import { secureStorage } from './secureStorage';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL as string;

/** Limpa tudo e redireciona para a rota informada. */
async function clearAndRedirect(path: string): Promise<void> {
    await biometric.clear();
    await secureStorage.clear();
    localStorage.removeItem('kalibrium.biometric_optout');
    window.location.replace(path);
}

/**
 * Wrapper de fetch que injeta headers de autenticação e trata wipe/401.
 * Aceita `path` relativo ao API_BASE_URL (ex: '/api/mobile/me').
 */
export async function apiFetch(path: string, init: RequestInit = {}): Promise<Response> {
    const token = await secureStorage.get('token');
    const deviceId = getDeviceIdentifier();

    const headers = new Headers(init.headers);
    headers.set('Accept', 'application/json');
    headers.set('Content-Type', 'application/json');
    headers.set('X-Device-Id', deviceId);
    if (token) {
        headers.set('Authorization', `Bearer ${token}`);
    }

    let response: Response;
    try {
        response = await fetch(`${API_BASE_URL}${path}`, { ...init, headers });
    } catch {
        throw new Error('network_error');
    }

    if (response.status === 401) {
        let body: Record<string, unknown> = {};
        try {
            body = (await response.clone().json()) as Record<string, unknown>;
        } catch {
            // corpo inválido — trata como 401 normal
        }

        if (body['wipe'] === true) {
            await clearAndRedirect('/blocked');
        } else {
            await secureStorage.remove('token');
            window.location.replace('/login');
        }
    }

    return response;
}

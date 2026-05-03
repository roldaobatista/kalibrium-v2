/**
 * Identificação do dispositivo.
 *
 * O device_id é persistido no armazenamento seguro (SQLite criptografado em
 * dispositivo nativo, IndexedDB em desktop) para que sobreviva a limpezas de
 * localStorage mas seja apagado junto com o banco no wipe remoto.
 *
 * getDeviceIdentifier() mantém assinatura síncrona para compatibilidade com
 * chamadas legadas (ex: api.ts que precisa do id antes de qualquer await).
 * O id é gerado na primeira chamada e cacheado em memória — a persistência
 * assíncrona ocorre em paralelo.
 *
 * Numa rodada futura, substituir por Capacitor Device plugin para obter
 * o identificador real do hardware (iOS/Android).
 */

import { secureStorage } from './secureStorage';

const STORAGE_KEY = 'device_id';

// Cache em memória para evitar leitura assíncrona no caminho crítico de auth.
let _cachedId: string | null = null;

/**
 * Inicializa o device_id a partir do armazenamento seguro.
 * Deve ser chamado após initSecureStorage() e antes do primeiro apiFetch.
 * Se já houver id salvo, carrega no cache. Se não, gera e persiste.
 */
export async function initDeviceIdentifier(): Promise<void> {
    const stored = await secureStorage.get(STORAGE_KEY);
    if (stored) {
        _cachedId = stored;
        return;
    }
    const newId = crypto.randomUUID();
    _cachedId = newId;
    await secureStorage.set(STORAGE_KEY, newId);
}

/**
 * Retorna o device_id em memória (síncrono).
 * Garante que initDeviceIdentifier() foi chamado antes (via main.tsx).
 * Se ainda não inicializado, gera um id temporário e dispara a persistência.
 */
export function getDeviceIdentifier(): string {
    if (_cachedId) return _cachedId;

    // Fallback de segurança: gera id temporário e tenta persistir.
    const tempId = crypto.randomUUID();
    _cachedId = tempId;
    void secureStorage.set(STORAGE_KEY, tempId);
    return tempId;
}

/**
 * Rótulo legível do dispositivo.
 *
 * Por enquanto usa o User-Agent truncado. Numa rodada futura, substituir por
 * Capacitor Device plugin para obter modelo e nome do aparelho (ex: "iPhone 15 de João").
 */
export function getDeviceLabel(): string {
    return navigator.userAgent.slice(0, 100);
}

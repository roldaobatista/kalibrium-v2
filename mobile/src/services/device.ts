/**
 * Identificação do dispositivo.
 *
 * Por enquanto usa localStorage + crypto.randomUUID() como identificador estável.
 * Numa rodada futura, substituir por Capacitor Device plugin para obter
 * o identificador real do hardware (iOS/Android).
 */

const STORAGE_KEY = 'kalibrium.device_id';

export function getDeviceIdentifier(): string {
    let id = localStorage.getItem(STORAGE_KEY);
    if (!id) {
        id = crypto.randomUUID();
        localStorage.setItem(STORAGE_KEY, id);
    }
    return id;
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

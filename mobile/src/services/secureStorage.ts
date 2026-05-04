/**
 * Armazenamento seguro — chave/valor criptografado.
 *
 * Em dispositivo Android/iOS (Capacitor nativo):
 *   - SQLite criptografado via SQLCipher (AES-256).
 *   - Banco: kalibrium.db, tabela kv_store.
 *   - A chave SQLCipher (32 bytes aleatórios) fica no Keychain/Keystore
 *     via @capacitor/preferences — FORA do próprio banco.
 *
 * Em navegador desktop (sem Capacitor nativo):
 *   - IndexedDB pura (sem criptografia — fallback aceitável conforme ADR-0015).
 *
 * IMPORTANTE: não abre conexão SQLite própria — usa o módulo db.ts central.
 */

import { Capacitor } from '@capacitor/core';
import { getSqliteDb, openIdb, initDb, clearDb } from './db';

// ---------------------------------------------------------------------------
// Interface pública
// ---------------------------------------------------------------------------

export interface SecureStorage {
    set(key: string, value: string): Promise<void>;
    get(key: string): Promise<string | null>;
    remove(key: string): Promise<void>;
    /** Apaga o banco inteiro e a chave SQLCipher do Keychain/Keystore. */
    clear(): Promise<void>;
}

// ---------------------------------------------------------------------------
// Implementação SQLite (nativa) — usa conexão central de db.ts
// ---------------------------------------------------------------------------

const sqliteStorage: SecureStorage = {
    async set(key: string, value: string): Promise<void> {
        await getSqliteDb().run(
            'INSERT INTO kv_store (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value;',
            [key, value],
        );
    },

    async get(key: string): Promise<string | null> {
        const result = await getSqliteDb().query('SELECT value FROM kv_store WHERE key = ?;', [
            key,
        ]);
        const row = result.values?.[0] as { value: string } | undefined;
        return row?.value ?? null;
    },

    async remove(key: string): Promise<void> {
        await getSqliteDb().run('DELETE FROM kv_store WHERE key = ?;', [key]);
    },

    async clear(): Promise<void> {
        await clearDb();
    },
};

// ---------------------------------------------------------------------------
// Implementação IndexedDB (fallback desktop) — usa conexão central de db.ts
// ---------------------------------------------------------------------------

const IDB_STORE = 'kv_store';

const idbStorage: SecureStorage = {
    async set(key: string, value: string): Promise<void> {
        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(IDB_STORE, 'readwrite');
            const req = tx.objectStore(IDB_STORE).put(value, key);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    },

    async get(key: string): Promise<string | null> {
        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(IDB_STORE, 'readonly');
            const req = tx.objectStore(IDB_STORE).get(key);
            req.onsuccess = () => resolve((req.result as string | undefined) ?? null);
            req.onerror = () => reject(req.error);
        });
    },

    async remove(key: string): Promise<void> {
        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(IDB_STORE, 'readwrite');
            const req = tx.objectStore(IDB_STORE).delete(key);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    },

    async clear(): Promise<void> {
        return new Promise((resolve, reject) => {
            const req = indexedDB.deleteDatabase('kalibrium');
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    },
};

// ---------------------------------------------------------------------------
// Instância única (singleton)
// ---------------------------------------------------------------------------

/**
 * Inicializa o armazenamento seguro.
 * DEVE ser chamado em main.tsx antes de renderizar a app.
 *
 * Em caso de falha crítica (ex: sem permissão de storage no Android),
 * lança um erro com mensagem em pt-BR para o main.tsx exibir na tela.
 */
export async function initSecureStorage(): Promise<void> {
    if (Capacitor.isNativePlatform()) {
        try {
            await initDb();
        } catch {
            throw new Error(
                'Não foi possível inicializar o armazenamento seguro. Reinstale o app.',
            );
        }
    }
    // IDB: sem init explícito necessário — openIdb() é lazy e idempotente.
}

/**
 * Instância do armazenamento seguro.
 * Só use após chamar initSecureStorage().
 */
export const secureStorage: SecureStorage = Capacitor.isNativePlatform()
    ? sqliteStorage
    : idbStorage;

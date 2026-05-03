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
 */

import { Capacitor } from '@capacitor/core';
import { Preferences } from '@capacitor/preferences';
import { CapacitorSQLite, SQLiteConnection, SQLiteDBConnection } from '@capacitor-community/sqlite';

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
// Constantes
// ---------------------------------------------------------------------------

const DB_NAME = 'kalibrium.db';
const PREF_KEY = 'kalibrium.db.key';
const IDB_DB_NAME = 'kalibrium';
const IDB_STORE = 'kv_store';

// ---------------------------------------------------------------------------
// Implementação SQLite (nativa)
// ---------------------------------------------------------------------------

class SqliteSecureStorage implements SecureStorage {
    private db: SQLiteDBConnection | null = null;
    private sqlite: SQLiteConnection;

    constructor() {
        this.sqlite = new SQLiteConnection(CapacitorSQLite);
    }

    async init(): Promise<void> {
        // Recupera ou gera a chave SQLCipher
        let { value: key } = await Preferences.get({ key: PREF_KEY });
        if (!key) {
            // Gera 32 bytes aleatórios e codifica em base64
            const bytes = new Uint8Array(32);
            crypto.getRandomValues(bytes);
            key = btoa(String.fromCharCode(...bytes));
            await Preferences.set({ key: PREF_KEY, value: key });
        }

        // Define a chave de criptografia ANTES de abrir qualquer banco.
        // O plugin usa setEncryptionSecret para repassar a passphrase ao SQLCipher.
        await this.sqlite.setEncryptionSecret(key);

        // Abre (ou cria) o banco criptografado com a passphrase já configurada.
        const db = await this.sqlite.createConnection(
            DB_NAME,
            true, // encrypted
            'secret',
            1,
            false,
        );
        await db.open();

        // Cria tabela se não existir
        await db.execute(
            'CREATE TABLE IF NOT EXISTS kv_store (key TEXT PRIMARY KEY, value TEXT NOT NULL);',
        );

        this.db = db;
    }

    private getDb(): SQLiteDBConnection {
        if (!this.db)
            throw new Error(
                'secureStorage não inicializado — chame initSecureStorage() antes de usar.',
            );
        return this.db;
    }

    async set(key: string, value: string): Promise<void> {
        await this.getDb().run(
            'INSERT INTO kv_store (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value;',
            [key, value],
        );
    }

    async get(key: string): Promise<string | null> {
        const result = await this.getDb().query('SELECT value FROM kv_store WHERE key = ?;', [key]);
        const row = result.values?.[0] as { value: string } | undefined;
        return row?.value ?? null;
    }

    async remove(key: string): Promise<void> {
        await this.getDb().run('DELETE FROM kv_store WHERE key = ?;', [key]);
    }

    async clear(): Promise<void> {
        // Apaga o banco físico via método delete() da conexão ativa,
        // depois apaga a chave do Keystore/Keychain.
        try {
            if (this.db) {
                await this.db.delete();
            }
        } catch {
            /* banco pode não existir ou já estar fechado — ignora */
        }
        this.db = null;

        await Preferences.remove({ key: PREF_KEY });
    }
}

// ---------------------------------------------------------------------------
// Implementação IndexedDB (fallback desktop)
// ---------------------------------------------------------------------------

function openIdb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(IDB_DB_NAME, 1);
        req.onupgradeneeded = () => {
            req.result.createObjectStore(IDB_STORE);
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

class IdbSecureStorage implements SecureStorage {
    async set(key: string, value: string): Promise<void> {
        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(IDB_STORE, 'readwrite');
            const req = tx.objectStore(IDB_STORE).put(value, key);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    }

    async get(key: string): Promise<string | null> {
        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(IDB_STORE, 'readonly');
            const req = tx.objectStore(IDB_STORE).get(key);
            req.onsuccess = () => resolve((req.result as string | undefined) ?? null);
            req.onerror = () => reject(req.error);
        });
    }

    async remove(key: string): Promise<void> {
        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(IDB_STORE, 'readwrite');
            const req = tx.objectStore(IDB_STORE).delete(key);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    }

    async clear(): Promise<void> {
        return new Promise((resolve, reject) => {
            const req = indexedDB.deleteDatabase(IDB_DB_NAME);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    }
}

// ---------------------------------------------------------------------------
// Instância única (singleton)
// ---------------------------------------------------------------------------

let _storage: SecureStorage | null = null;

/**
 * Inicializa o armazenamento seguro.
 * DEVE ser chamado em main.tsx antes de renderizar a app.
 *
 * Em caso de falha crítica (ex: sem permissão de storage no Android),
 * lança um erro com mensagem em pt-BR para o main.tsx exibir na tela.
 */
export async function initSecureStorage(): Promise<void> {
    if (_storage) return; // já inicializado

    if (Capacitor.isNativePlatform()) {
        const impl = new SqliteSecureStorage();
        try {
            await impl.init();
        } catch (err) {
            throw new Error(
                'Não foi possível inicializar o armazenamento seguro. Reinstale o app.',
            );
        }
        _storage = impl;
    } else {
        _storage = new IdbSecureStorage();
    }
}

/**
 * Instância do armazenamento seguro.
 * Só use após chamar initSecureStorage().
 */
export const secureStorage: SecureStorage = {
    set: (k, v) => getInstance().set(k, v),
    get: (k) => getInstance().get(k),
    remove: (k) => getInstance().remove(k),
    clear: () => getInstance().clear(),
};

function getInstance(): SecureStorage {
    if (!_storage) {
        throw new Error('secureStorage não inicializado — chame initSecureStorage() primeiro.');
    }
    return _storage;
}

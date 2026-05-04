/**
 * db.ts — ponto central de acesso ao banco SQLite/IDB.
 *
 * Regra: UMA única conexão para o banco inteiro. Todos os services importam
 * daqui; nenhum abre conexão própria.
 *
 * Nativo (Android/iOS via Capacitor):
 *   SQLite criptografado (SQLCipher AES-256), banco kalibrium.db.
 *   Chave SQLCipher guardada no Keychain/Keystore via @capacitor/preferences.
 *
 * Desktop (sem Capacitor):
 *   IndexedDB, banco "kalibrium", versão 2 com todos os object stores.
 */

import { Capacitor } from '@capacitor/core';
import { Preferences } from '@capacitor/preferences';
import { CapacitorSQLite, SQLiteConnection, SQLiteDBConnection } from '@capacitor-community/sqlite';

// ---------------------------------------------------------------------------
// Constantes
// ---------------------------------------------------------------------------

const DB_NAME = 'kalibrium.db';
const PREF_KEY = 'kalibrium.db.key';
const IDB_DB_NAME = 'kalibrium';
const IDB_VERSION = 3;

// ---------------------------------------------------------------------------
// SQLite — conexão única
// ---------------------------------------------------------------------------

let _sqliteConn: SQLiteDBConnection | null = null;

async function getOrCreateSqliteKey(): Promise<string> {
    let { value: key } = await Preferences.get({ key: PREF_KEY });
    if (!key) {
        const bytes = new Uint8Array(32);
        crypto.getRandomValues(bytes);
        key = btoa(String.fromCharCode(...bytes));
        await Preferences.set({ key: PREF_KEY, value: key });
    }
    return key;
}

/**
 * Inicializa a conexão SQLite e cria todas as tabelas necessárias.
 * Deve ser chamado uma única vez, antes de usar qualquer service.
 */
export async function initDb(): Promise<void> {
    if (!Capacitor.isNativePlatform()) return; // IDB não precisa de init explícito
    if (_sqliteConn) return; // já inicializado

    const key = await getOrCreateSqliteKey();
    const sqlite = new SQLiteConnection(CapacitorSQLite);
    await sqlite.setEncryptionSecret(key);

    // Se o banco já existe, reutiliza a conexão existente em vez de criar nova.
    const exists = (await sqlite.isDatabase(DB_NAME)).result;
    let db: SQLiteDBConnection;
    if (exists) {
        db = await sqlite.retrieveConnection(DB_NAME, false);
    } else {
        db = await sqlite.createConnection(DB_NAME, true, 'secret', 1, false);
    }
    await db.open();

    // Migrations — executadas na ordem, idempotentes via IF NOT EXISTS.
    await db.execute(`
        CREATE TABLE IF NOT EXISTS kv_store (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
    `);
    await db.execute(`
        CREATE TABLE IF NOT EXISTS notes (
            id           TEXT PRIMARY KEY,
            server_id    TEXT,
            title        TEXT NOT NULL,
            body         TEXT NOT NULL,
            updated_at   TEXT NOT NULL,
            pending_sync INTEGER NOT NULL DEFAULT 1,
            deleted      INTEGER NOT NULL DEFAULT 0
        );
    `);
    await db.execute(`
        CREATE TABLE IF NOT EXISTS sync_outbox (
            local_id    TEXT PRIMARY KEY,
            entity_type TEXT NOT NULL,
            entity_id   TEXT NOT NULL,
            action      TEXT NOT NULL,
            payload     TEXT NOT NULL,
            created_at  INTEGER NOT NULL,
            attempts    INTEGER NOT NULL DEFAULT 0
        );
    `);
    await db.execute(`
        CREATE TABLE IF NOT EXISTS sync_state (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
    `);
    await db.execute(`
        CREATE TABLE IF NOT EXISTS service_orders (
            id                     TEXT PRIMARY KEY,
            server_id              TEXT,
            client_name            TEXT NOT NULL,
            instrument_description TEXT NOT NULL,
            status                 TEXT NOT NULL DEFAULT 'received',
            notes                  TEXT,
            updated_at             TEXT NOT NULL,
            pending_sync           INTEGER NOT NULL DEFAULT 1,
            deleted                INTEGER NOT NULL DEFAULT 0
        );
    `);

    _sqliteConn = db;
}

/**
 * Retorna a conexão SQLite ativa. Lança se initDb() não foi chamado.
 */
export function getSqliteDb(): SQLiteDBConnection {
    if (!_sqliteConn) {
        throw new Error('db não inicializado — chame initDb() antes de usar.');
    }
    return _sqliteConn;
}

/**
 * Remove a chave SQLCipher e apaga o banco. Para uso em logout/clear.
 */
export async function clearDb(): Promise<void> {
    try {
        if (_sqliteConn) {
            await _sqliteConn.delete();
        }
    } catch {
        /* ignora se banco não existia */
    }
    _sqliteConn = null;
    await Preferences.remove({ key: PREF_KEY });
}

// ---------------------------------------------------------------------------
// IndexedDB — conexão única (desktop)
// ---------------------------------------------------------------------------

let _idb: IDBDatabase | null = null;

export function openIdb(): Promise<IDBDatabase> {
    if (_idb) return Promise.resolve(_idb);

    return new Promise((resolve, reject) => {
        const req = indexedDB.open(IDB_DB_NAME, IDB_VERSION);

        req.onupgradeneeded = (evt) => {
            const db = req.result;
            const oldVer = evt.oldVersion;

            if (oldVer < 1) {
                db.createObjectStore('kv_store');
            }
            if (oldVer < 2) {
                const noteStore = db.createObjectStore('notes', { keyPath: 'id' });
                noteStore.createIndex('updated_at', 'updated_at');
                db.createObjectStore('sync_outbox', { keyPath: 'local_id' });
                db.createObjectStore('sync_state', { keyPath: 'key' });
            }
            if (oldVer < 3) {
                const soStore = db.createObjectStore('service_orders', { keyPath: 'id' });
                soStore.createIndex('updated_at', 'updated_at');
                soStore.createIndex('status', 'status');
            }
        };

        req.onsuccess = () => {
            _idb = req.result;
            resolve(_idb);
        };
        req.onerror = () => reject(req.error);
    });
}

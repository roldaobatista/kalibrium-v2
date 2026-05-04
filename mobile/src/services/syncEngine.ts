/**
 * SyncEngine — fila local de mudanças + sincronização com o servidor.
 *
 * Fluxo:
 *  1. recordChange() salva a mudança em SQLite local (tabelas sync_outbox e notes).
 *  2. flushOutbox() envia lotes de até 100 mudanças para /api/mobile/sync/push.
 *  3. pull() busca mudanças do servidor e aplica no SQLite local.
 *  4. start() inicia loop de 30s + listeners online/offline.
 *  5. stop() limpa interval e listeners.
 *
 * IMPORTANTE: não abre conexão SQLite própria — usa o módulo db.ts central.
 * Todas as tabelas (notes, sync_outbox, sync_state) são criadas pelo initDb().
 */

import { Capacitor } from '@capacitor/core';
import { getSqliteDb, openIdb } from './db';
import { apiFetch } from './api';

// ---------------------------------------------------------------------------
// ULID simples (sem dependência externa)
// ---------------------------------------------------------------------------

const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

function generateUlid(): string {
    const now = Date.now();
    let t = now;
    const timeChars: string[] = [];
    for (let i = 9; i >= 0; i--) {
        timeChars[i] = ENCODING[t % 32]!;
        t = Math.floor(t / 32);
    }

    const randChars: string[] = [];
    for (let i = 0; i < 16; i++) {
        randChars.push(ENCODING[Math.floor(Math.random() * 32)]!);
    }

    return timeChars.join('') + randChars.join('');
}

// ---------------------------------------------------------------------------
// Interfaces
// ---------------------------------------------------------------------------

export type SyncAction = 'create' | 'update' | 'delete';

export interface OutboxEntry {
    local_id: string;
    entity_type: string;
    entity_id: string;
    action: SyncAction;
    payload: Record<string, unknown>;
    created_at: number; // ms timestamp
    attempts: number;
}

export interface NoteRow {
    id: string;
    server_id: string | null;
    title: string;
    body: string;
    updated_at: string; // ISO string
    pending_sync: number; // 0 or 1
    deleted: number; // 0 or 1
}

// ---------------------------------------------------------------------------
// SyncEngine
// ---------------------------------------------------------------------------

class SyncEngineImpl {
    private intervalId: ReturnType<typeof setInterval> | null = null;
    private onlineHandler: (() => void) | null = null;
    private offlineHandler: (() => void) | null = null;

    // ----------------------------------------------------------------
    // recordChange — salva mudança na outbox e aplica localmente
    // ----------------------------------------------------------------

    async recordChange(
        entity: string,
        action: SyncAction,
        payload: Record<string, unknown>,
    ): Promise<string> {
        const localId = generateUlid();
        const entityId = (payload['id'] as string | undefined) ?? localId;

        const entry: OutboxEntry = {
            local_id: localId,
            entity_type: entity,
            entity_id: entityId,
            action,
            payload,
            created_at: Date.now(),
            attempts: 0,
        };

        if (Capacitor.isNativePlatform()) {
            await this.recordChangeSqlite(entry, payload);
        } else {
            await this.recordChangeIdb(entry, payload);
        }

        return localId;
    }

    private async recordChangeSqlite(
        entry: OutboxEntry,
        payload: Record<string, unknown>,
    ): Promise<void> {
        const db = getSqliteDb();

        await db.run(
            `INSERT INTO sync_outbox (local_id, entity_type, entity_id, action, payload, created_at, attempts)
             VALUES (?, ?, ?, ?, ?, ?, 0);`,
            [
                entry.local_id,
                entry.entity_type,
                entry.entity_id,
                entry.action,
                JSON.stringify(payload),
                entry.created_at,
            ],
        );

        if (entry.entity_type === 'note') {
            if (entry.action === 'create') {
                await db.run(
                    `INSERT OR REPLACE INTO notes (id, server_id, title, body, updated_at, pending_sync, deleted)
                     VALUES (?, NULL, ?, ?, ?, 1, 0);`,
                    [
                        entry.local_id,
                        String(payload['title'] ?? ''),
                        String(payload['body'] ?? ''),
                        String(payload['updated_at'] ?? new Date().toISOString()),
                    ],
                );
            } else if (entry.action === 'update') {
                await db.run(
                    `UPDATE notes SET title=?, body=?, updated_at=?, pending_sync=1
                     WHERE id=? OR server_id=?;`,
                    [
                        String(payload['title'] ?? ''),
                        String(payload['body'] ?? ''),
                        String(payload['updated_at'] ?? new Date().toISOString()),
                        entry.entity_id,
                        entry.entity_id,
                    ],
                );
            } else if (entry.action === 'delete') {
                await db.run(
                    `UPDATE notes SET deleted=1, pending_sync=1 WHERE id=? OR server_id=?;`,
                    [entry.entity_id, entry.entity_id],
                );
            }
        }
    }

    private async recordChangeIdb(
        entry: OutboxEntry,
        payload: Record<string, unknown>,
    ): Promise<void> {
        const db = await openIdb();
        const tx = db.transaction(['sync_outbox', 'notes'], 'readwrite');

        tx.objectStore('sync_outbox').put(entry);

        if (entry.entity_type === 'note') {
            const noteStore = tx.objectStore('notes');
            if (entry.action === 'create') {
                noteStore.put({
                    id: entry.local_id,
                    server_id: null,
                    title: String(payload['title'] ?? ''),
                    body: String(payload['body'] ?? ''),
                    updated_at: String(payload['updated_at'] ?? new Date().toISOString()),
                    pending_sync: 1,
                    deleted: 0,
                } satisfies NoteRow);
            } else if (entry.action === 'update') {
                const existing = await new Promise<NoteRow | undefined>((resolve, reject) => {
                    const req = noteStore.get(entry.entity_id);
                    req.onsuccess = () => resolve(req.result as NoteRow | undefined);
                    req.onerror = () => reject(req.error);
                });
                if (existing) {
                    noteStore.put({
                        ...existing,
                        title: String(payload['title'] ?? existing.title),
                        body: String(payload['body'] ?? existing.body),
                        updated_at: String(payload['updated_at'] ?? existing.updated_at),
                        pending_sync: 1,
                    });
                }
            } else if (entry.action === 'delete') {
                const existing = await new Promise<NoteRow | undefined>((resolve, reject) => {
                    const req = noteStore.get(entry.entity_id);
                    req.onsuccess = () => resolve(req.result as NoteRow | undefined);
                    req.onerror = () => reject(req.error);
                });
                if (existing) {
                    noteStore.put({ ...existing, deleted: 1, pending_sync: 1 });
                }
            }
        }

        await new Promise<void>((resolve, reject) => {
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    // ----------------------------------------------------------------
    // flushOutbox — envia lote ao servidor
    // ----------------------------------------------------------------

    async flushOutbox(): Promise<void> {
        const entries = await this.getOutboxEntries(100);
        if (entries.length === 0) return;

        const deviceId = localStorage.getItem('kalibrium.device_id') ?? 'unknown';

        const body = {
            device_id: deviceId,
            changes: entries.map((e) => ({
                local_id: e.local_id,
                entity_type: e.entity_type,
                entity_id: e.entity_id,
                action: e.action,
                payload: e.payload,
            })),
        };

        let response: Response;
        try {
            response = await apiFetch('/api/mobile/sync/push', {
                method: 'POST',
                body: JSON.stringify(body),
            });
        } catch {
            // Falha de rede — mantém na outbox para tentar depois
            return;
        }

        if (!response.ok) return;

        interface PushResponse {
            applied: { local_id: string; server_id: string }[];
            rejected: { local_id: string; reason: string }[];
        }

        const result = (await response.json()) as PushResponse;

        // Remove aplicados e atualiza server_id nas notas
        const appliedIds = result.applied.map((a) => a.local_id);
        const serverIdMap = new Map(result.applied.map((a) => [a.local_id, a.server_id]));

        await this.markOutboxApplied(appliedIds, serverIdMap);

        // Remove rejeitados definitivos da outbox (erros permanentes)
        const permanentErrors = ['unknown_entity_type', 'unknown_action', 'forbidden'];
        const rejectedPermanent = result.rejected
            .filter((r) => permanentErrors.includes(r.reason))
            .map((r) => r.local_id);

        if (rejectedPermanent.length > 0) {
            await this.removeFromOutbox(rejectedPermanent);
        }
    }

    private async getOutboxEntries(limit: number): Promise<OutboxEntry[]> {
        if (Capacitor.isNativePlatform()) {
            const result = await getSqliteDb().query(
                `SELECT * FROM sync_outbox ORDER BY created_at ASC LIMIT ?;`,
                [limit],
            );
            return (result.values ?? []).map((row) => ({
                ...(row as Omit<OutboxEntry, 'payload'>),
                payload: JSON.parse(String((row as { payload: string }).payload)) as Record<
                    string,
                    unknown
                >,
            }));
        }

        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('sync_outbox', 'readonly');
            const req = tx.objectStore('sync_outbox').getAll();
            req.onsuccess = () => {
                const all = (req.result as OutboxEntry[]).sort(
                    (a, b) => a.created_at - b.created_at,
                );
                resolve(all.slice(0, limit));
            };
            req.onerror = () => reject(req.error);
        });
    }

    private async markOutboxApplied(
        localIds: string[],
        serverIdMap: Map<string, string>,
    ): Promise<void> {
        if (localIds.length === 0) return;

        if (Capacitor.isNativePlatform()) {
            const db = getSqliteDb();
            for (const localId of localIds) {
                await db.run(`DELETE FROM sync_outbox WHERE local_id=?;`, [localId]);
                const serverId = serverIdMap.get(localId);
                if (serverId) {
                    await db.run(`UPDATE notes SET server_id=?, pending_sync=0 WHERE id=?;`, [
                        serverId,
                        localId,
                    ]);
                }
            }
        } else {
            const db = await openIdb();
            const tx = db.transaction(['sync_outbox', 'notes'], 'readwrite');
            for (const localId of localIds) {
                tx.objectStore('sync_outbox').delete(localId);
                const serverId = serverIdMap.get(localId);
                if (serverId) {
                    const noteReq = tx.objectStore('notes').get(localId);
                    noteReq.onsuccess = () => {
                        const note = noteReq.result as NoteRow | undefined;
                        if (note) {
                            tx.objectStore('notes').put({
                                ...note,
                                server_id: serverId,
                                pending_sync: 0,
                            });
                        }
                    };
                }
            }
            await new Promise<void>((resolve, reject) => {
                tx.oncomplete = () => resolve();
                tx.onerror = () => reject(tx.error);
            });
        }
    }

    private async removeFromOutbox(localIds: string[]): Promise<void> {
        if (Capacitor.isNativePlatform()) {
            const db = getSqliteDb();
            for (const id of localIds) {
                await db.run(`DELETE FROM sync_outbox WHERE local_id=?;`, [id]);
            }
        } else {
            const db = await openIdb();
            const tx = db.transaction('sync_outbox', 'readwrite');
            for (const id of localIds) {
                tx.objectStore('sync_outbox').delete(id);
            }
            await new Promise<void>((resolve, reject) => {
                tx.oncomplete = () => resolve();
                tx.onerror = () => reject(tx.error);
            });
        }
    }

    // ----------------------------------------------------------------
    // pull — busca mudanças do servidor e aplica localmente
    // ----------------------------------------------------------------

    async pull(): Promise<void> {
        const cursor = await this.getSyncCursor();
        const url = cursor
            ? `/api/mobile/sync/pull?cursor=${encodeURIComponent(cursor)}`
            : '/api/mobile/sync/pull';

        let response: Response;
        try {
            response = await apiFetch(url);
        } catch {
            return;
        }

        if (!response.ok) return;

        interface PullResponse {
            changes: {
                ulid: string;
                entity_type: string;
                entity_id: string;
                action: string;
                payload: Record<string, unknown> | null;
            }[];
            next_cursor: string | null;
            has_more: boolean;
        }

        const data = (await response.json()) as PullResponse;

        for (const change of data.changes) {
            if (change.entity_type === 'note') {
                await this.applyNoteChange(change.entity_id, change.action, change.payload);
            }
        }

        if (data.next_cursor) {
            await this.setSyncCursor(data.next_cursor);
        }

        // Se tem mais, continua paginando
        if (data.has_more) {
            await this.pull();
        }
    }

    private async applyNoteChange(
        serverId: string,
        action: string,
        payload: Record<string, unknown> | null,
    ): Promise<void> {
        if (Capacitor.isNativePlatform()) {
            const db = getSqliteDb();
            if (action === 'delete') {
                await db.run(`UPDATE notes SET deleted=1, pending_sync=0 WHERE server_id=?;`, [
                    serverId,
                ]);
            } else if (action === 'create' || action === 'update') {
                await db.run(
                    `INSERT INTO notes (id, server_id, title, body, updated_at, pending_sync, deleted)
                     VALUES (?, ?, ?, ?, ?, 0, 0)
                     ON CONFLICT(id) DO UPDATE SET
                         server_id=excluded.server_id,
                         title=excluded.title,
                         body=excluded.body,
                         updated_at=excluded.updated_at,
                         pending_sync=0,
                         deleted=0;`,
                    [
                        serverId,
                        serverId,
                        String(payload?.['title'] ?? ''),
                        String(payload?.['body'] ?? ''),
                        String(payload?.['updated_at'] ?? new Date().toISOString()),
                    ],
                );
            }
        } else {
            const db = await openIdb();
            const tx = db.transaction('notes', 'readwrite');
            const store = tx.objectStore('notes');

            if (action === 'delete') {
                const req = store.index('updated_at').getAll();
                req.onsuccess = () => {
                    const notes = req.result as NoteRow[];
                    const note = notes.find((n) => n.server_id === serverId);
                    if (note) {
                        store.put({ ...note, deleted: 1, pending_sync: 0 });
                    }
                };
            } else {
                store.put({
                    id: serverId,
                    server_id: serverId,
                    title: String(payload?.['title'] ?? ''),
                    body: String(payload?.['body'] ?? ''),
                    updated_at: String(payload?.['updated_at'] ?? new Date().toISOString()),
                    pending_sync: 0,
                    deleted: 0,
                } satisfies NoteRow);
            }

            await new Promise<void>((resolve, reject) => {
                tx.oncomplete = () => resolve();
                tx.onerror = () => reject(tx.error);
            });
        }
    }

    private async getSyncCursor(): Promise<string | null> {
        if (Capacitor.isNativePlatform()) {
            const result = await getSqliteDb().query(
                `SELECT value FROM sync_state WHERE key='pull_cursor';`,
            );
            const row = result.values?.[0] as { value: string } | undefined;
            return row?.value ?? null;
        }

        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const req = db
                .transaction('sync_state', 'readonly')
                .objectStore('sync_state')
                .get('pull_cursor');
            req.onsuccess = () => {
                const row = req.result as { key: string; value: string } | undefined;
                resolve(row?.value ?? null);
            };
            req.onerror = () => reject(req.error);
        });
    }

    private async setSyncCursor(cursor: string): Promise<void> {
        if (Capacitor.isNativePlatform()) {
            await getSqliteDb().run(
                `INSERT INTO sync_state (key, value) VALUES ('pull_cursor', ?)
                 ON CONFLICT(key) DO UPDATE SET value=excluded.value;`,
                [cursor],
            );
        } else {
            const db = await openIdb();
            await new Promise<void>((resolve, reject) => {
                const tx = db.transaction('sync_state', 'readwrite');
                tx.objectStore('sync_state').put({ key: 'pull_cursor', value: cursor });
                tx.oncomplete = () => resolve();
                tx.onerror = () => reject(tx.error);
            });
        }
    }

    // ----------------------------------------------------------------
    // getNotes — lista notas do banco local
    // ----------------------------------------------------------------

    async getNotes(): Promise<NoteRow[]> {
        if (Capacitor.isNativePlatform()) {
            const result = await getSqliteDb().query(
                `SELECT * FROM notes WHERE deleted=0 ORDER BY updated_at DESC;`,
            );
            return (result.values ?? []) as NoteRow[];
        }

        const db = await openIdb();
        return new Promise((resolve, reject) => {
            const req = db.transaction('notes', 'readonly').objectStore('notes').getAll();
            req.onsuccess = () => {
                const all = (req.result as NoteRow[])
                    .filter((n) => !n.deleted)
                    .sort((a, b) => b.updated_at.localeCompare(a.updated_at));
                resolve(all);
            };
            req.onerror = () => reject(req.error);
        });
    }

    // ----------------------------------------------------------------
    // start / stop
    // ----------------------------------------------------------------

    start(): void {
        if (this.intervalId !== null) return; // já iniciado

        const sync = () => {
            void this.pull().then(() => this.flushOutbox());
        };

        // Sincroniza imediatamente ao iniciar
        sync();

        this.intervalId = setInterval(sync, 30_000);

        this.onlineHandler = () => sync();
        window.addEventListener('online', this.onlineHandler);

        this.offlineHandler = () => {
            // Offline: mantém outbox, não tenta enviar
        };
        window.addEventListener('offline', this.offlineHandler);
    }

    stop(): void {
        if (this.intervalId !== null) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }

        if (this.onlineHandler) {
            window.removeEventListener('online', this.onlineHandler);
            this.onlineHandler = null;
        }

        if (this.offlineHandler) {
            window.removeEventListener('offline', this.offlineHandler);
            this.offlineHandler = null;
        }
    }
}

export const syncEngine = new SyncEngineImpl();

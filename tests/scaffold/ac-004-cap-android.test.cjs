// @covers AC-004
//
// Slice 016 — E15-S02
// AC-004: npx cap add android && npx cap sync android gera android/build.gradle + android/app/build.gradle com exit 0.

'use strict';

const { describe, test } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const ANDROID_DIR = path.join(REPO_ROOT, 'android');
const NPX_CMD = os.platform() === 'win32' ? 'npx.cmd' : 'npx';

describe('AC-004: Capacitor Android platform added and syncable', () => {
    test('AC-004: npx cap add android exits with code 0 (or already exists)', () => {
        const res = spawnSync(NPX_CMD, ['cap', 'add', 'android'], {
            cwd: REPO_ROOT,
            shell: false,
            encoding: 'utf8',
            timeout: 600_000,
            env: { ...process.env },
        });
        const combined = `${res.stdout || ''}\n${res.stderr || ''}`;
        const alreadyExists = /already\s+exists|android\s+platform\s+is\s+already/i.test(combined);
        assert.ok(
            res.status === 0 || alreadyExists,
            `npx cap add android must exit 0 (or already exist). status=${res.status}\n${combined}`,
        );
    });

    test('AC-004: npx cap sync android exits with code 0', () => {
        const res = spawnSync(NPX_CMD, ['cap', 'sync', 'android'], {
            cwd: REPO_ROOT,
            shell: false,
            encoding: 'utf8',
            timeout: 600_000,
            env: { ...process.env },
        });
        assert.equal(
            res.status,
            0,
            `npx cap sync android must exit 0. status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
        );
    });

    test('AC-004: android/build.gradle exists', () => {
        const p = path.join(ANDROID_DIR, 'build.gradle');
        assert.ok(fs.existsSync(p), `android/build.gradle must exist after cap add+sync: ${p}`);
    });

    test('AC-004: android/app/build.gradle exists', () => {
        const p = path.join(ANDROID_DIR, 'app', 'build.gradle');
        assert.ok(fs.existsSync(p), `android/app/build.gradle must exist after cap add+sync: ${p}`);
    });

    test('AC-004: android/settings.gradle exists (required for root Gradle project)', () => {
        const p = path.join(ANDROID_DIR, 'settings.gradle');
        assert.ok(fs.existsSync(p), `android/settings.gradle must exist after cap add+sync: ${p}`);
    });
});

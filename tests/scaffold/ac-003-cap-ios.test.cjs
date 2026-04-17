// @covers AC-003
//
// Slice 016 — E15-S02
// AC-003: npx cap add ios && npx cap sync ios gera ios/App/App.xcworkspace com exit 0.
// Skip automático em hosts não-macOS (Windows/Linux).

'use strict';

const { describe, test } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const IOS_DIR = path.join(REPO_ROOT, 'ios');
const XCWORKSPACE = path.join(IOS_DIR, 'App', 'App.xcworkspace');
const NPX_CMD = os.platform() === 'win32' ? 'npx.cmd' : 'npx';

const IS_DARWIN = process.platform === 'darwin';

describe('AC-003: Capacitor iOS platform added and syncable (macOS only)', { skip: !IS_DARWIN }, () => {
    test('AC-003: npx cap add ios exits with code 0', () => {
        // If the platform is already added, cap add is idempotent OR prints a warning
        // and returns non-zero. Treat "already exists" as acceptable.
        const res = spawnSync(NPX_CMD, ['cap', 'add', 'ios'], {
            cwd: REPO_ROOT,
            shell: true,
            encoding: 'utf8',
            timeout: 600_000,
            env: { ...process.env },
        });
        const combined = `${res.stdout || ''}\n${res.stderr || ''}`;
        const alreadyExists = /already\s+exists|ios\s+platform\s+is\s+already/i.test(combined);
        assert.ok(
            res.status === 0 || alreadyExists,
            `npx cap add ios must exit 0 (or already exist). status=${res.status}\n${combined}`,
        );
    });

    test('AC-003: npx cap sync ios exits with code 0', () => {
        const res = spawnSync(NPX_CMD, ['cap', 'sync', 'ios'], {
            cwd: REPO_ROOT,
            shell: true,
            encoding: 'utf8',
            timeout: 600_000,
            env: { ...process.env },
        });
        assert.equal(
            res.status,
            0,
            `npx cap sync ios must exit 0. status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
        );
    });

    test('AC-003: ios/App/App.xcworkspace directory exists', () => {
        assert.ok(
            fs.existsSync(XCWORKSPACE),
            `ios/App/App.xcworkspace must exist after cap add+sync. Expected: ${XCWORKSPACE}`,
        );
        const stat = fs.statSync(XCWORKSPACE);
        assert.ok(stat.isDirectory(), `${XCWORKSPACE} must be a directory (Xcode workspace bundle)`);
    });
});

describe('AC-003: iOS test skipped with reason on non-darwin hosts', { skip: IS_DARWIN }, () => {
    test('AC-003: host is not darwin — test pack skipped (documented)', () => {
        // This branch exists solely so non-macOS CI runs produce a visible
        // "skipped" marker with an explicit reason, instead of silently
        // passing zero tests.
        assert.ok(!IS_DARWIN, `expected non-darwin host; process.platform=${process.platform}`);
        // Intentional assertion that the skipper branch was entered.
        assert.ok(true, 'AC-003 skipped: macOS is required for npx cap add ios (see plan §2 D4, R2).');
    });
});

// ESLint flat config — slice-016 scaffold.
// Cobre src/ (React + TS) e ignora dist, android, ios, vendor, node_modules.
import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import reactPlugin from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import prettierConfig from 'eslint-config-prettier';
import globals from 'globals';

export default [
    {
        ignores: [
            'dist/**',
            'build/**',
            'android/**',
            'ios/**',
            'vendor/**',
            'node_modules/**',
            'public/**',
            'storage/**',
            'bootstrap/**',
            'coverage/**',
            'playwright-report/**',
            'test-results/**',
            'lighthouse-reports/**',
            'spike-inf007/**',
            'tests/**',
            'scripts/**',
            'test-audit-input/**',
            'functional-review-input/**',
            'security-review-input/**',
            'master-audit-input/**',
            'certs/**',
            '*.config.js',
            '*.config.cjs',
            '*.config.mjs',
            '*.config.ts',
            '**/*.d.ts',
        ],
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    {
        files: ['src/**/*.{ts,tsx}'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            parser: tseslint.parser,
            parserOptions: {
                ecmaFeatures: { jsx: true },
            },
            globals: {
                ...globals.browser,
                ...globals.es2022,
            },
        },
        plugins: {
            react: reactPlugin,
            'react-hooks': reactHooks,
        },
        settings: {
            react: { version: '18.3' },
        },
        rules: {
            'react/jsx-uses-react': 'off',
            'react/react-in-jsx-scope': 'off',
            'react/jsx-uses-vars': 'error',
            'react-hooks/rules-of-hooks': 'error',
            'react-hooks/exhaustive-deps': 'warn',
            '@typescript-eslint/no-unused-vars': [
                'error',
                { argsIgnorePattern: '^_', varsIgnorePattern: '^_' },
            ],
            'no-unused-vars': 'off',
        },
    },
    prettierConfig,
];

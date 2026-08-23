import eslint from '@eslint/js';
import { withVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import eslintConfigPrettier from 'eslint-config-prettier';
import pluginVue from 'eslint-plugin-vue';

export default withVueTs(
    { ignores: ['vendor/**', 'node_modules/**', 'public/build/**', 'review-packets/**', 'design-prototypes/**'] },
    eslint.configs.recommended,
    pluginVue.configs['flat/essential'],
    vueTsConfigs.recommended,
    eslintConfigPrettier,
    { rules: { 'vue/multi-word-component-names': 'off', 'vue/one-component-per-file': 'off' } },
    {
        files: ['scripts/ci/browser_evidence.mjs'],
        languageOptions: {
            globals: {
                Buffer: 'readonly',
                WebSocket: 'readonly',
                fetch: 'readonly',
                process: 'readonly',
                setTimeout: 'readonly',
            },
        },
        rules: {
            'no-empty': ['error', { allowEmptyCatch: true }],
        },
    },
);

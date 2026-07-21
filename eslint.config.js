import eslint from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    { ignores: ['vendor/**', 'node_modules/**', 'public/build/**', 'review-packets/**'] },
    eslint.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    eslintConfigPrettier,
    { rules: { 'vue/multi-word-component-names': 'off' } },
);

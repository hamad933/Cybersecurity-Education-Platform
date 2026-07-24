import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['resources/js/tests/**/*.spec.ts'],
    setupFiles: ['./resources/js/tests/setup.ts'],
    pool: 'vmThreads',
    maxWorkers: 1,
    fileParallelism: false,
  },
});

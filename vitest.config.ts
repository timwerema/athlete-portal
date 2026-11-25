import { defineConfig } from 'vitest/config';

export default defineConfig({
    esbuild: { jsx: 'automatic' },
    resolve: { alias: { '@': new URL('./resources/js', import.meta.url).pathname } },
    test: {
        include: ['resources/js/**/*.test.ts'],
        environment: 'node',
    },
});

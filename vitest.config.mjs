import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: false,
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
            '@': path.resolve(process.cwd(), 'resources/js'),
        },
    },
    test: {
        environment: 'jsdom',
        setupFiles: ['tests/frontend/setup.js'],
        include: ['tests/frontend/**/*.spec.js'],
        restoreMocks: true,
    },
});

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import ui from '@nuxt/ui/vite'
import NuxtUiTheme from './src/theme/NuxtUiTheme'
import checker from 'vite-plugin-checker'
import path from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: ['./src/app.ts'],
            publicDirectory: './../../public',
            refresh: true,
        }),
        vue(),
        ui({
            ui: NuxtUiTheme,
        }),
        checker({
            vueTsc: {
                tsconfigPath: './tsconfig.app.json',
            },
        }),
    ],
    server: {
        host: true,
        port: 3000,
        strictPort: true,

        hmr: {
            host: 'localhost',
            protocol: 'ws',
            port: 3000,
            clientPort: 3000,
        },

        cors: {
            origin: 'http://localhost:81',
        },
    },
    resolve: {
        alias: {
            '@src': path.resolve(__dirname, 'src'),
            '@lctypes': path.resolve(__dirname, 'src/types'),
            '@components': path.resolve(__dirname, 'src/components'),
            '@services': path.resolve(__dirname, 'src/services'),
            '@store': path.resolve(__dirname, 'src/store'),
            '@assets': path.resolve(__dirname, 'src/assets'),
        },
    },
})

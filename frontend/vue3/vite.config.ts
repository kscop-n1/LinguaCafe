import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import ui from '@nuxt/ui/vite'
import NuxtUiTheme from './src/theme/NuxtUiTheme'

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
})

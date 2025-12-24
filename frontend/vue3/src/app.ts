import App from './App.vue'
import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import ui from '@nuxt/ui/vue-plugin'
import { routes } from '@src/routes.ts'
import moment from 'moment'

import '@assets/main.css'

moment.updateLocale('en', {
    week: {
        dow: 1,
    },
})

moment.locale('en')

const router = createRouter({
    history: createWebHistory(),
    routes,
})

const app = createApp(App)

app.provide('moment', moment)

app.use(ui)
app.use(router)

app.mount('#app')

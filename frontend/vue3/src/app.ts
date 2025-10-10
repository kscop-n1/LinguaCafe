import App from './App.vue'
import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import ui from '@nuxt/ui/vue-plugin'
import './assets/main.css'
import { routes } from './routes'

const router = createRouter({
    history: createWebHistory(),
    routes,
})

const app = createApp(App)

app.use(ui)
app.use(router)

app.mount('#app')

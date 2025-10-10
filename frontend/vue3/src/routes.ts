import type { RouteRecordRaw } from 'vue-router'

// components
import Home from './components/Home.vue'
import Login from './components/Login.vue'

export const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'Home',
        component: Home,
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
]

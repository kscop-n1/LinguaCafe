import type { RouteRecordRaw } from 'vue-router'

import Home from '@components/home/HomePage.vue'
import Login from '@components/auth/Login.vue'

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

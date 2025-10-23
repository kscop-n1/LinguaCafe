import type { RouteRecordRaw } from 'vue-router'

import Home from '@src/components/Home.vue'
import Login from '@src/components/auth/Login.vue'

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

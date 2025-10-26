import type { RouteRecordRaw } from 'vue-router'

import Home from '@components/home/HomePage.vue'
import Login from '@components/auth/Login.vue'
import Attributions from '@components/home/Attributions.vue'

export const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'Home',
        component: Home,
    },
    {
        path: '/attributions',
        name: 'Attributions',
        component: Attributions,
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
]

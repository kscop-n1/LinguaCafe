import type { RouteRecordRaw } from 'vue-router'

import Home from '@components/home/HomePage.vue'
import Attributions from '@components/home/Attributions.vue'
import UpdateNotes from '@components/home/UpdateNotes.vue'
import Login from '@components/auth/Login.vue'

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
        path: '/update-notes',
        name: 'Update notes',
        component: UpdateNotes,
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
]

<script setup lang="ts">
import { ref } from 'vue'
import type { NavigationMenuItem } from '@nuxt/ui'
import axios from 'axios'
const logout = function () {
    axios({
        method: 'POST',
        url: '/api/auth/logout',
    }).then(() => {
        window.location.href = '/'
    })
}

const collapsed = ref(false)
const NavigationMenuItems = ref<NavigationMenuItem[][]>([
    [
        {
            label: 'Home',
            type: 'link',
            href: '/',
            icon: 'i-lucide-house',
        },
        {
            label: 'Library',
            type: 'link',
            href: '/library',
            icon: 'i-lucide-library-square',
        },
        {
            label: 'Vocabulary',
            type: 'link',
            href: '/vocabulary',
            icon: 'i-lucide-notebook',
        },
        {
            label: 'Admin',
            type: 'link',
            href: '/admin',
            icon: 'i-lucide-shield',
        },
        {
            label: 'Settings',
            type: 'link',
            href: '/settings',
            icon: 'i-lucide-settings',
        },
        {
            label: 'Logout',
            type: 'link',
            icon: 'i-lucide-log-out',
            onSelect: logout,
        },
    ],
])

defineShortcuts({
    's-c': () => {
        collapsed.value = !collapsed.value
        console.log('ok', collapsed.value)
    },
})
</script>

<template>
    <UDashboardGroup>
        <UDashboardSidebar :collapsible="true" :collapsed="collapsed" mode="drawer" :open="true">
            <template #header>
                <div class="w-full text-center">LinguaCafe</div>
            </template>

            <UNavigationMenu orientation="vertical" :items="NavigationMenuItems" class="w-60" />

            <template #footer>
                <UColorModeSelect
                    class="w-64"
                    :ui="{
                        base: 'rounded-full',
                    }"
                />
            </template>
        </UDashboardSidebar>
    </UDashboardGroup>
</template>

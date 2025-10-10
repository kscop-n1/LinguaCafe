<script setup lang="ts">
// Imports
import { onMounted, ref } from 'vue'

// Type imports
import type { NavigationMenuItem } from '@nuxt/ui'

// Variables
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
            href: '/logout',
            icon: 'i-lucide-log-out',
        },
    ],
])

// Lifecycle hooks
onMounted(() => {})

// Methods
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

            <UNavigationMenu
                orientation="vertical"
                :items="NavigationMenuItems"
                class="w-60"
                :ui="{
                    link: 'before:rounded-full py-2',
                }"
            />

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

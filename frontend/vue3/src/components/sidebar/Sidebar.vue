<script setup lang="ts">
import { ref } from 'vue'
import LogoutPopup from '@components/popups/auth/LogoutPopup.vue'
import store from '@store/Store'

import type { NavigationMenuItem } from '@nuxt/ui'

defineShortcuts({
    [store.settings.shortcuts.sidebar.toggleCollapse]: () => {
        collapsed.value = !collapsed.value
    },
})

const selectedNavigationMenuItem = ref()
const showLogoutPopup = ref<boolean>(false)
const collapsed = ref<boolean>(false)
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
            tooltip: {
                text: 'Library',
            },
        },
        {
            label: 'Vocabulary',
            type: 'link',
            href: '/vocabulary',
            icon: 'i-lucide-notebook',
            badge: '1,234',
            tooltip: {
                text: 'Vocabulary',
            },
        },
        {
            label: 'Admin',
            type: 'link',
            href: '/admin',
            icon: 'i-lucide-shield',
            tooltip: {
                text: 'Admin',
            },
        },
        {
            label: 'Settings',
            type: 'link',
            href: '/settings',
            icon: 'i-lucide-settings',
            tooltip: {
                text: 'Settings',
            },
        },
        {
            label: 'Logout',
            type: 'link',
            icon: 'i-lucide-log-out',
            tooltip: {
                text: 'Logout',
                kbds: ['s-l'],
            },
            onSelect: () => {
                showLogoutPopup.value = true
            },
        },
    ],
])
</script>

<template>
    <UDashboardGroup>
        <LogoutPopup v-model="showLogoutPopup" />

        <UDashboardSidebar class="max-w-[300px]" :collapsible="true" :collapsed="collapsed">
            <div class="w-full mt-12 mb-4" v-if="!collapsed">
                <div class="w-full flex justify-center items-center">
                    <UAvatar src="/icon512rounded.png" class="mr-2 rounded-lg" size="md" />
                    <span>LinguaCafe</span>
                </div>
            </div>
            <UAvatar
                src="/icon512rounded.png"
                v-else
                class="mx-auto mt-12 mb-4 rounded-xl"
                size="2xl"
            />

            <UNavigationMenu
                v-model="selectedNavigationMenuItem"
                :class="[collapsed ? 'w-12 ml-2 rounded-xl' : 'pr-4']"
                orientation="vertical"
                :items="NavigationMenuItems"
                :collapsed="collapsed"
                :ui="{
                    link: collapsed ? 'justify-center before:rounded-xl rounded-xl py-3' : '',
                    linkLeadingIcon: collapsed ? 'size-5' : '',
                }"
                tooltip
            >
            </UNavigationMenu>

            <SidebarFooter
                :collapsed="collapsed"
                @toggle-sidebar-collapse="collapsed = !collapsed"
                @logout="showLogoutPopup = true"
            />
        </UDashboardSidebar>
    </UDashboardGroup>
</template>

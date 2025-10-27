<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import LogoutPopup from '@components/popups/auth/LogoutPopup.vue'
import store from '@store/Store'

import type { NavigationMenuItem } from '@nuxt/ui'

defineShortcuts({
    [store.settings.shortcuts.sidebar.toggleCollapse]: () => {
        store.sidebarCollapsed = !store.sidebarCollapsed
    },
})

const route = useRoute()

const homeHighlighted = computed(() => {
    return route.path === '/' || route.path === '/attributions'
})

const selectedNavigationMenuItem = ref()
const showLogoutPopup = ref<boolean>(false)
const NavigationMenuItems = computed<NavigationMenuItem[][]>(() => {
    return [
        [
            {
                label: 'Home',
                type: 'link',
                href: '/',
                icon: 'i-lucide-house',
                active: homeHighlighted.value,
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
                tooltip: {
                    text: 'Vocabulary',
                },
            },
            {
                label: 'Review',
                type: 'link',
                href: '/review',
                icon: 'i-lucide-book-check',
                badge: '1,234',
                tooltip: {
                    text: 'Review',
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
    ]
})
</script>

<template>
    <UDashboardGroup>
        <LogoutPopup v-model="showLogoutPopup" />

        <UDashboardSidebar
            class="fixed left-0 top-0"
            :class="[store.sidebarCollapsed ? 'w-[65px]' : 'w-[300px]']"
            :collapsible="true"
            :collapsed="store.sidebarCollapsed"
        >
            <div class="w-full mt-12 mb-4" v-if="!store.sidebarCollapsed">
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
                :class="[store.sidebarCollapsed ? 'w-12 ml-2 rounded-xl' : 'pr-4 w-full']"
                orientation="vertical"
                :items="NavigationMenuItems"
                :collapsed="store.sidebarCollapsed"
                :ui="{
                    link: store.sidebarCollapsed
                        ? 'justify-center before:rounded-xl rounded-xl py-3'
                        : '',
                    linkLeadingIcon: store.sidebarCollapsed ? 'size-5' : '',
                }"
                tooltip
            >
            </UNavigationMenu>

            <SidebarFooter
                v-if="store.user"
                :collapsed="store.sidebarCollapsed"
                @toggle-sidebar-collapse="store.sidebarCollapsed = !store.sidebarCollapsed"
                @logout="showLogoutPopup = true"
            />
        </UDashboardSidebar>
    </UDashboardGroup>
</template>

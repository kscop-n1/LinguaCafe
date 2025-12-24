<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'
import Sidebar from '@src/components/sidebar/Sidebar.vue'
import Store from '@store/Store'
import AppService from '@services/users/AppService'

const appService = new AppService()

const setStoreWindowSize = () => {
    Store.window.width = window.innerWidth
    Store.window.height = window.innerHeight

    if (Store.window.width >= 1024 && Store.sidebarCollapsed) {
        Store.window.widthWithoutSidebar = Store.window.width - 65
    }

    if (Store.window.width >= 1024 && !Store.sidebarCollapsed) {
        Store.window.widthWithoutSidebar = Store.window.width - 300
    }
}

onMounted(() => {
    appService.initializeStore()

    window.addEventListener('resize', setStoreWindowSize)
    setStoreWindowSize()
})

onUnmounted(() => {
    window.removeEventListener('resize', setStoreWindowSize)
})

watch(
    () => Store.sidebarCollapsed,
    () => {
        setStoreWindowSize()
    }
)
</script>

<template>
    <template v-if="Store.appDataInitialized">
        <div
            v-if="Store.user"
            class="w-full flex flex-wrap"
            :class="[Store.sidebarCollapsed ? 'lg:pl-[65px]' : 'lg:pl-[300px]']"
        >
            <Sidebar />
            <UContainer :class="[Store.sidebarCollapsed ? 'box-border' : 'box-border']"
                ><RouterView
            /></UContainer>
        </div>
        <UPage v-else>
            <RouterView />
        </UPage>
    </template>
</template>

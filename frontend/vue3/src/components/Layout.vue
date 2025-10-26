<script setup lang="ts">
import Sidebar from '@src/components/sidebar/Sidebar.vue'
import store from '@store/Store'
import { onMounted } from 'vue'
import AppService from '@services/users/AppService'

const appService = new AppService()

onMounted(() => {
    appService.initializeStore()
})
</script>

<template>
    <template v-if="store.appDataInitialized">
        <div v-if="store.user" class="w-full flex flex-wrap">
            <Sidebar />
            <UContainer
                :class="[
                    store.sidebarCollapsed
                        ? 'box-border lg:max-w-[calc(100%-105px)]'
                        : 'box-border lg:max-w-[calc(100%-300px)]',
                ]"
                ><RouterView
            /></UContainer>
        </div>
        <UPage v-else>
            <RouterView />
        </UPage>
    </template>
</template>

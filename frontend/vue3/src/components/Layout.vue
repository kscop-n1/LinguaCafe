<script setup lang="ts">
import Sidebar from '@components/sidebar/Sidebar.vue'
import Store from '@store/Store'
import { onMounted } from 'vue'
import AppService from '@services/users/AppService'

const appService = new AppService()

onMounted(() => {
    appService.initializeStore()
})
</script>

<template>
    <template v-if="Store.appDataInitialized">
        <UPage v-if="Store.user">
            <template #left>
                <UPageAside>
                    <Sidebar />
                </UPageAside>
            </template>

            <UPageBody
                ><UContainer><RouterView /></UContainer
            ></UPageBody>
        </UPage>
        <UPage v-else>
            <UContainer>
                <UPageBody
                    ><UContainer><RouterView /></UContainer
                ></UPageBody>
            </UContainer>
        </UPage>
    </template>
</template>

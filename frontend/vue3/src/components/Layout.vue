<script setup lang="ts">
import Sidebar from '@src/components/Sidebar.vue'
import Store from '@src/store/Store'
</script>

<template>
    <UPage>
        <template #left>
            <UPageAside v-if="Store.user">
                <Sidebar />
            </UPageAside>
        </template>

        <UPageBody><RouterView /></UPageBody>
    </UPage>
</template>

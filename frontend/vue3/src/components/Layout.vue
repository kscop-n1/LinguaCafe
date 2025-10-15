<script setup lang="ts">
import Sidebar from './Sidebar.vue'
</script>

<template>
    <UPage>
        <template #left>
            <UPageAside>
                <Sidebar />
            </UPageAside>
        </template>

        <UPageBody><RouterView /></UPageBody>
    </UPage>
</template>

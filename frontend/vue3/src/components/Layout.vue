<script setup lang="ts">
import Sidebar from '@src/components/sidebar/Sidebar.vue'
import Store from '@store/Store'
import { onMounted } from 'vue'
import axios from 'axios'

onMounted(() => {
    axios({
        method: 'GET',
        url: '/api/users/data',
    })
        .then(response => {
            Store.user = response.data.user ?? null
            Store.hasUser = response.data.userCount > 1
            Store.language = response.data.user.selected_language ?? null
        })
        .catch(() => {
            //
        })
})
</script>

<template>
    <UPage v-if="Store.user">
        <template #left>
            <UPageAside>
                <Sidebar />
            </UPageAside>
        </template>

        <UPageBody><RouterView /></UPageBody>
    </UPage>
    <UPage v-else>
        <UPageBody><RouterView /></UPageBody>
    </UPage>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Store from '@store/Store'

const showCreateUserPopup = ref(false)
</script>

<template>
    <div>
        <CreateUserPopup v-model="showCreateUserPopup" />
        <UAlert
            v-if="Store"
            variant="subtle"
            color="neutral"
            icon="i-lucide-user-round-plus"
            class="mb-4"
        >
            <template #title>First user</template>
            <template #description>
                It seems like it is your first time using LinguaCafe after installing it, and you
                haven't created a user yet.
            </template>
            <template #actions>
                <div class="w-full flex justify-end">
                    <UButton
                        color="primary"
                        size="sm"
                        label="Create first user"
                        @click="showCreateUserPopup = true"
                    />
                </div>
            </template>
        </UAlert>
    </div>
</template>

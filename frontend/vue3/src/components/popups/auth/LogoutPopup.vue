<script setup lang="ts">
import AuthService from '@src/services/users/AuthService'
import { computed } from 'vue'

const authService = new AuthService()

type Props = {
    modelValue: boolean
}

const { modelValue } = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])
const modalOpened = computed({
    get: () => modelValue,
    set: value => emit('update:modelValue', value),
})

const logout = function () {
    authService.logout()
}
</script>

<template>
    <UModal title="Modal with title" v-model:open="modalOpened" :dismissible="false">
        <template #title> Logout </template>
        <template #body> Are you sure you want to logout? </template>
        <template #footer>
            <div class="w-full flex justify-end">
                <UButton
                    label="Cancel"
                    variant="link"
                    color="neutral"
                    @click="modalOpened = false"
                />
                <UButton class="ml-2" label="Confirm" color="primary" @click="logout" />
            </div>
        </template>
    </UModal>
</template>

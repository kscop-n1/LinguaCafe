<script setup lang="ts">
import type { FormError } from '@nuxt/ui'

type Props = {
    title: string
    errorMessages?: FormError[]
}

const { title = 'title', errorMessages = [] as FormError[] } = defineProps<Props>()
</script>

<template>
    <UAlert color="error" icon="i-lucide-alert-triangle" :title="title" v-if="errorMessages.length">
        <template #description>
            <div class="my-2" v-for="(errorMessage, errorIndex) in errorMessages" :key="errorIndex">
                <template v-if="errorMessage.name">
                    <span class="font-bold">{{ errorMessage.name }}</span
                    >: {{ errorMessage.message }}
                </template>
                <template v-else>
                    {{ errorMessage.message }}
                </template>
            </div>
        </template>
    </UAlert>
</template>

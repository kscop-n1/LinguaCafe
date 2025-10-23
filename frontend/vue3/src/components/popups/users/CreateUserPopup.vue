<script setup lang="ts">
import { computed } from 'vue'

type Props = {
    modelValue: boolean
}

const { modelValue } = defineProps<Props>()
const modalOpened = computed({
    get: () => modelValue,
    set: value => emit('update:modelValue', value),
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <UModal title="Modal with title" v-model:open="modalOpened" :dismissible="false">
        <template #body> teszt </template>
    </UModal>
</template>

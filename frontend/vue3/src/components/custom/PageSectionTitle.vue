<script setup lang="ts">
type Props = {
    title: string
}

const { title } = defineProps<Props>()
</script>

<template>
    <UPageHeader
        :title="title"
        :ui="{
            root: 'py-2 border-0',
            title: 'text-xl sm:text-xl',
        }"
    />
</template>

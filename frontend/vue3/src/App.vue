<script setup lang="ts">
import { onMounted } from 'vue'

onMounted(() => {
    // temp code for setting the theme
    const newColors = {
        50: '#f7f7f8',
        100: '#eeedf1',
        200: '#d9d8df',
        300: '#b7b6c3',
        400: '#908ea2',
        500: '#727087',
        600: '#5d5a6f',
        700: '#4d4a5a',
        800: '#413f4d',
        900: '#3a3842',
        950: '#1c1b20',
    }

    for (const [key, value] of Object.entries(newColors)) {
        document.documentElement.style.setProperty(`--ui-color-neutral-${key}`, value)
    }

    document.documentElement.style.setProperty(`--ui-primary`, '#C5947D')
})
</script>

<template>
    <UApp>
        <Layout />
    </UApp>
</template>

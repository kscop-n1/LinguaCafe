<script setup lang="ts">
import { onMounted } from 'vue'

onMounted(() => {
    // temp code for setting the theme
    const neutralColors = {
        50: '#dadadd',
        100: '#cfced3',
        200: '#bbbbc1',
        300: '#a8a8b0',
        400: '#96959f',
        500: '#84828e',
        600: '#72717e',
        700: '#615f6d',
        800: '#504f5c',
        900: '#403f4a',
        950: '#393842',
    }
    for (const [key, value] of Object.entries(neutralColors)) {
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

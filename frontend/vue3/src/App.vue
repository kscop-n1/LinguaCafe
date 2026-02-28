<script setup lang="ts">
import { onMounted } from 'vue'

onMounted(() => {
    // temp code for setting the theme
    const neutralColors = {
    //     50: '#dadadd',
    //     100: '#cfced3',
    //     200: '#bbbbc1',
    //     300: '#a8a8b0',
    //     400: '#96959f',
    //     500: '#84828e',
    //     600: '#72717e',
    //     700: '#615f6d',
    //     800: '#504f5c',
    //     900: '#403f4a',
    //     950: '#393842',

        50:  "#f6f6f7",
        100: "#ededee",
        200: "#dadadb",
        300: "#c3c3c5",
        400: "#a6a6a8",
        500: "#89898a",
        600: "#6d6d6f",
        700: "#575759",
        800: "#444446",
        900: "#383839",
        950: "#2d2d2f",
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

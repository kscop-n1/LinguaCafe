<script setup lang="ts">
import attributions from '@config/Attributions'
</script>

<template>
    <UCard v-for="(attribution, index) in attributions" :key="index">
        {{ attribution.name }}
    </UCard>
</template>

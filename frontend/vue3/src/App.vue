<script setup lang="ts">
import { ref } from 'vue'

const items = ref([
    {
        title: 'Address',
        description: 'Add your address here',
        icon: 'i-lucide-house',
    },
    {
        title: 'Shipping',
        description: 'Set your preferred shipping method',
        icon: 'i-lucide-truck',
    },
    {
        title: 'Checkout',
        description: 'Confirm your order',
    },
])
</script>

<template>
    <div class="pt-24">
        <div class="w-full px-8 text-4xl text-center">Hello world!</div>
        <UStepper :items="items" />
    </div>
</template>

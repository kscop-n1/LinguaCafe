<script setup lang="ts">
import { ref } from 'vue'

const linguaCafeLinks = ref([
    {
        label: 'Attributions',
        icon: 'i-lucide-copyright',
        to: '/attributions',
    },
    {
        label: 'Overview',
        icon: 'i-lucide-layout-template',
        to: 'https://simjanos-dev.github.io/LinguaCafeHome/',
    },
])
</script>

<template>
    <div class="user-select">
        <UPageGrid>
            <UPageCard>
                <template #title
                    ><div class="flex items-center">
                        <UIcon
                            class="text-primary mr-2 size-5"
                            name="i-lucide-cup-soda"
                        />LinguaCafe
                    </div></template
                >
                <template #description>
                    <UPageLinks class="mt-4" :links="linguaCafeLinks" />
                </template>
            </UPageCard>
            <UPageCard>
                <template #title
                    ><div class="flex items-center">
                        <UIcon class="text-primary mr-2 size-5" name="i-lucide-contact" />Contact
                    </div></template
                >
                <template #description>
                    <UPageLinks class="mt-4" :links="[]" />
                </template>
            </UPageCard>
            <UPageCard>
                <template #title
                    ><div class="flex items-center">
                        <UIcon class="text-primary mr-2 size-5" name="i-lucide-history" />Version
                    </div></template
                >
                <template #description>
                    <UPageLinks class="mt-4" :links="[]" />
                </template>
            </UPageCard>
        </UPageGrid>
    </div>
</template>

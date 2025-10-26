<script setup lang="ts">
import { ref } from 'vue'
import HomePageAbout from '@components/home/sections/HomePageAbout.vue'
import PageSectionTitle from '@components/custom/PageSectionTitle.vue'

const homePageSections = ref([
    {
        title: 'About',
        component: 'HomePageAbout',
    },
])
</script>

<template>
    <div class="user-select">
        <template v-for="(homePageSection, index) in homePageSections" :key="index">
            <PageSectionTitle :title="homePageSection.title" />
            <HomePageAbout v-if="homePageSection.component === 'HomePageAbout'" />
        </template>
    </div>
</template>

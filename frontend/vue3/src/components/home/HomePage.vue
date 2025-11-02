<script setup lang="ts">
import { ref } from 'vue'
import PageSectionTitle from '@components/custom/PageSectionTitle.vue'
import ContentSpacer from '@components/custom/ContentSpacer.vue'

import HomePageAbout from '@components/home/sections/about/HomePageAbout.vue'
import HomePageDailyGoals from '@components/home/sections/goals/HomePageDailyGoals.vue'
import HomePageStatistics from '@components/home/sections/statistics/HomePageStatistics.vue'

const homePageSections = ref([
    {
        title: 'Daily goals',
        component: 'HomePageDailyGoals',
    },
    {
        title: 'Statistics',
        component: 'HomePageStatistics',
    },
    {
        title: 'About',
        component: 'HomePageAbout',
    },
])
</script>

<template>
    <ContentSpacer class="user-select">
        <template v-for="(homePageSection, index) in homePageSections" :key="index">
            <PageSectionTitle :title="homePageSection.title" />

            <HomePageAbout v-if="homePageSection.component === 'HomePageAbout'" />
            <HomePageDailyGoals v-if="homePageSection.component === 'HomePageDailyGoals'" />
            <HomePageStatistics v-if="homePageSection.component === 'HomePageStatistics'" />
        </template>
    </ContentSpacer>
</template>

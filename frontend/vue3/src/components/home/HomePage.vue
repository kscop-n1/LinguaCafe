<script setup lang="ts">
import { computed } from 'vue'
import PageSectionTitle from '@components/custom/PageSectionTitle.vue'
import ContentSpacer from '@components/custom/ContentSpacer.vue'
import Store from '@src/store/Store'

import HomePageAbout from '@components/home/sections/about/HomePageAbout.vue'
import HomePageCalendar from '@components/home/sections/calendar/HomePageCalendar.vue'
import HomePageDailyGoals from '@components/home/sections/goals/HomePageDailyGoals.vue'
import HomePagePasswordChange from '@components/home/sections/password/HomePagePasswordChange.vue'
import HomePageStatistics from '@components/home/sections/statistics/HomePageStatistics.vue'

type HomePageSection = {
    title?: string
    component: string
    visible: boolean
}

const homePageSections = computed<HomePageSection[]>(() => [
    {
        title: 'Password change',
        component: 'HomePagePasswordChange',
        visible: !Store.user?.password_changed,
    },
    {
        component: 'HomePageCalendar',
        visible: true,
    },
    {
        title: 'Daily goals',
        component: 'HomePageDailyGoals',
        visible: true,
    },
    {
        title: 'Statistics',
        component: 'HomePageStatistics',
        visible: true,
    },
    {
        title: 'About',
        component: 'HomePageAbout',
        visible: true,
    },
])
</script>

<template>
    <ContentSpacer class="user-select">
        <template v-for="(homePageSection, index) in homePageSections" :key="index">
            <template v-if="homePageSection.visible">
                <PageSectionTitle
                    v-if="homePageSection.title"
                    :title="homePageSection.title"
                    :class="[index > 0 ? 'mt-8' : '']"
                />

                <HomePageAbout v-if="homePageSection.component === 'HomePageAbout'" />
                <HomePageCalendar v-if="homePageSection.component === 'HomePageCalendar'" />
                <HomePageDailyGoals v-if="homePageSection.component === 'HomePageDailyGoals'" />
                <HomePageStatistics v-if="homePageSection.component === 'HomePageStatistics'" />
                <HomePagePasswordChange
                    v-if="homePageSection.component === 'HomePagePasswordChange'"
                />
            </template>
        </template>
    </ContentSpacer>
</template>

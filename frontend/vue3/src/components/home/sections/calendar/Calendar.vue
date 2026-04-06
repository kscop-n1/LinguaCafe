<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import CalendarService from '@services/calendar/CalendarService'
import { useMoment } from '@composables/useMoment'
import MonthPicker from '@components/custom/MonthPicker.vue'
import { formatGoalType } from '@src/helpers/GoalHelper'
import { toUpperCase } from '@src/helpers/StringHelper'

import { CalendarSelectableStatEnum } from '@lctypes/calendar/Calendar'
import type { Calendar } from '@lctypes/calendar/Calendar'
import type { Moment } from 'moment'

const emit = defineEmits(['goalsUpdated'])

type Props = {
    isHeatmap: boolean
}

const { isHeatmap } = defineProps<Props>()

const selectedCalendarGoalType = ref(CalendarSelectableStatEnum.ReadWords)

const calendarGoalTypes = ref([
    {
        label: toUpperCase(formatGoalType(CalendarSelectableStatEnum.ReadWords) ?? '', true),
        value: CalendarSelectableStatEnum.ReadWords,
    },
    {
        label: toUpperCase(formatGoalType(CalendarSelectableStatEnum.Review) ?? '', true),
        value: CalendarSelectableStatEnum.Review,
    },
    {
        label: toUpperCase(formatGoalType(CalendarSelectableStatEnum.LearnWords) ?? '', true),
        value: CalendarSelectableStatEnum.LearnWords,
    },
    {
        label: toUpperCase(formatGoalType(CalendarSelectableStatEnum.ReviewsDue) ?? '', true),
        value: CalendarSelectableStatEnum.ReviewsDue,
    },
])

const calendarService = new CalendarService()
const moment = useMoment()
const calendarData = ref<Calendar | null>(null)
const loading = ref<boolean>(false)
const selectedDate = ref<Moment>(moment())

const mostDueReviews = computed(() => {
    let mostDueReviews = 0

    if (!calendarData.value) {
        return mostDueReviews
    }

    Object.values(calendarData.value.reviews).forEach(review => {
        if (review.quantity > mostDueReviews) {
            mostDueReviews = review.quantity
        }
    })

    return mostDueReviews
})

const loadGoals = async function () {
    loading.value = true
    const response = await calendarService.getCalendarData()
    loading.value = false

    if (response.ok && response.data) {
        calendarData.value = response.data ?? null
    }
}

onMounted(() => {
    loadGoals()
})
</script>

<template>
    <div>
        <PageSectionTitle
            :title="isHeatmap ? 'Heatmap calendar' : 'Calendar'"
            class="mt-8 leading-12"
        >
            <template #right>
                <div class="flex items-center">
                    <USelect
                        v-model="selectedCalendarGoalType"
                        class="w-36 rounded-xl mr-2"
                        variant="subtle"
                        :items="calendarGoalTypes"
                    />

                    <MonthPicker v-model:value="selectedDate" />
                </div>
            </template>
        </PageSectionTitle>

        <div class="flex flex-wrap w-full justify-between gap-x-2 mt-2">
            <CalendarYearHeatmap
                v-if="isHeatmap && calendarData"
                :calendar-data="calendarData"
                :month="selectedDate"
                :selected-goal="selectedCalendarGoalType"
                :most-due-reviews="mostDueReviews"
            />

            <CalendarMonth
                v-if="!isHeatmap && calendarData"
                :calendar-data="calendarData"
                :month="selectedDate"
                :selected-goal="selectedCalendarGoalType"
                :most-due-reviews="mostDueReviews"
                @goals-updated="loadGoals"
            />
        </div>
    </div>
</template>

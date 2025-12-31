<script setup lang="ts">
import { ref, computed, shallowRef, onMounted } from 'vue'
import CalendarService from '@services/calendar/CalendarService'
import { useMoment } from '@composables/useMoment'
import { CalendarDate } from '@internationalized/date'
import Store from '@src/store/Store'

import { CalendarSelectableStatEnum } from '@lctypes/calendar/Calendar'
import type { Calendar } from '@lctypes/calendar/Calendar'
import type { Moment } from 'moment'

type Props = {
    isHeatmap: boolean
}

const { isHeatmap } = defineProps<Props>()

const selectedCalendarGoalType = ref(CalendarSelectableStatEnum.ReadWords)

const calendarGoalTypes = ref([
    {
        label: 'Reading',
        value: CalendarSelectableStatEnum.ReadWords,
    },
    {
        label: 'Reviews',
        value: CalendarSelectableStatEnum.Review,
    },
    {
        label: 'New words',
        value: CalendarSelectableStatEnum.LearnWords,
    },
    {
        label: 'Reviews due',
        value: CalendarSelectableStatEnum.ReviewsDue,
    },
])

const calendarService = new CalendarService()
const moment = useMoment()
const calendarData = ref<Calendar | null>(null)
const loading = ref<boolean>(false)
const selectedDate = shallowRef(
    new CalendarDate(moment().year(), moment().month() + 1, moment().day())
)

const visibleMonthsCount = computed<number>(() => {
    if (Store.window.widthWithoutSidebar < 900) return 1
    if (Store.window.widthWithoutSidebar < 1450) return 2
    return 3
})

const datePickerOpened = ref<boolean>(false)

const selectedMonths = computed<Moment[]>(() => {
    let months: Moment[] = []

    const selectedDateString = `${selectedDate.value.year}-${selectedDate.value.month}-${
        selectedDate.value.day < 10 ? '0' + selectedDate.value.day : selectedDate.value.day
    }`
    let currentDate = moment(selectedDateString)

    do {
        months.push(currentDate.clone())
        currentDate.subtract(1, 'month')
    } while (months.length < visibleMonthsCount.value)

    return months.reverse()
})

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

const closeDatePicker = function () {
    datePickerOpened.value = false
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

                    <UPopover v-model:open="datePickerOpened">
                        <UButton
                            class="w-36 d-flex justify-center"
                            color="neutral"
                            variant="subtle"
                            icon="i-lucide-calendar"
                        >
                            {{
                                selectedDate
                                    ? selectedDate.year +
                                      '-' +
                                      String(selectedDate.month).padStart(2, '0')
                                    : 'Select a date'
                            }}
                        </UButton>

                        <template #content>
                            <UCalendar
                                v-model="selectedDate"
                                class="p-2"
                                @update:modelValue="closeDatePicker"
                            />
                        </template>
                    </UPopover>
                </div>
            </template>
        </PageSectionTitle>

        <div class="flex flex-wrap w-full justify-start gap-x-2 mt-2">
            <template v-if="isHeatmap">
                <HomePageCalendarYearHeatmap
                    v-if="calendarData && selectedMonths[0] !== undefined"
                    :calendar-data="calendarData"
                    :month="selectedMonths[0]"
                    :selected-goal="selectedCalendarGoalType"
                    :most-due-reviews="mostDueReviews"
                />
            </template>

            <template v-if="!isHeatmap">
                <template v-if="calendarData">
                    <HomePageCalendarMonth
                        v-for="(month, monthIndex) in selectedMonths"
                        :key="monthIndex"
                        :calendar-data="calendarData"
                        :month="month"
                        :selected-goal="selectedCalendarGoalType"
                        :most-due-reviews="mostDueReviews"
                    />
                </template>
            </template>
        </div>
    </div>
</template>

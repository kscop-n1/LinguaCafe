<script setup lang="ts">
import { ref, computed, shallowRef, onMounted } from 'vue'
import HomePageCalendarMonth from '@components/home/sections/calendar/HomePageCalendarMonth.vue'
import CalendarService from '@services/calendar/CalendarService'
import { useMoment } from '@composables/useMoment'
import { CalendarDate } from '@internationalized/date'
import Store from '@src/store/Store'

import type { Calendar } from '@lctypes/calendar/Calendar'
import type { Moment } from 'moment'
import { GoalType } from '@lctypes/goals/Goal'

const selectedCalendarGoalType = ref(GoalType.ReadWords)

const calendarGoalTypes = ref([
    {
        label: 'Reading',
        value: GoalType.ReadWords,
    },
    {
        label: 'Reviews',
        value: GoalType.Review,
    },
    {
        label: 'New words',
        value: GoalType.LearnWords,
    },
    {
        label: 'Reviews due',
        value: 'reviews_due',
    },
])

const calendarService = new CalendarService()
const moment = useMoment()
const calendar = ref<Calendar | null>(null)
const loading = ref<boolean>(false)
const selectedDate = shallowRef(
    new CalendarDate(moment().year(), moment().month() + 1, moment().day())
)

const visibleMonthsCount = computed<number>(() => {
    if (Store.window.widthWithoutSidebar < 1100) return 1
    return 2
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

const loadGoals = async function () {
    loading.value = true
    const response = await calendarService.getCalendarData()
    loading.value = false

    if (response.ok && response.data) {
        calendar.value = response.data ?? null
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
        <PageSectionTitle title="Calendar" class="mt-8 leading-12">
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

        <div class="flex w-full justify-between gap-4 mt-2">
            <HomePageCalendarMonth
                v-for="(selectedMonth, index) in selectedMonths"
                v-if="calendar"
                :key="index"
                :month="selectedMonth"
                :calendar="calendar"
                :selected-goal="selectedCalendarGoalType"
            />
        </div>
    </div>
</template>

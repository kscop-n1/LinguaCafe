<script setup lang="ts">
import { ref, computed, shallowRef, onMounted } from 'vue'
import CalendarService from '@services/calendar/CalendarService'
import { useMoment } from '@composables/useMoment'
import { CalendarDate } from '@internationalized/date'

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

const datePickerOpened = ref<boolean>(false)

const selectedMonth = computed<Moment>(() => {
    const selectedDateString = `${selectedDate.value.year}-${selectedDate.value.month}-${
        selectedDate.value.day < 10 ? '0' + selectedDate.value.day : selectedDate.value.day
    }`
    
    let currentDate = moment(selectedDateString)

    return currentDate
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
                <CalendarYearHeatmap
                    v-if="calendarData && selectedMonth !== undefined"
                    :calendar-data="calendarData"
                    :month="selectedMonth"
                    :selected-goal="selectedCalendarGoalType"
                    :most-due-reviews="mostDueReviews"
                />
            </template>

            <template v-if="!isHeatmap && calendarData">
                <CalendarMonth
                    :calendar-data="calendarData"
                    :month="selectedMonth"
                    :selected-goal="selectedCalendarGoalType"
                    :most-due-reviews="mostDueReviews"
                />
            </template>
        </div>
    </div>
</template>

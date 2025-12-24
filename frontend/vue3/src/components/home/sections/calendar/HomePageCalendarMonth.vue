<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useMoment } from '@composables/useMoment'

import type { Moment } from 'moment'
import type { Calendar } from '@lctypes/calendar/Calendar'
import type { CalendarWeek } from '@lctypes/calendar/CalendarWeek'
import type { CalendarDay } from '@lctypes/calendar/CalendarDay'
import { isGoalType } from '@lctypes/goals/Goal'

type Props = {
    calendar: Calendar
    month: Moment
    selectedGoal: string
}

const { calendar, month, selectedGoal } = defineProps<Props>()
const weeksOfMonth = ref<CalendarWeek[]>([])
const moment = useMoment()

const getDayColorClasses = (day: CalendarDay): string => {
    let achievedQuantity =
        calendar.goals['read_words']?.goalAchievements[day.date]?.achieved_quantity ?? null
    let goalQuantity =
        calendar.goals['read_words']?.goalAchievements[day.date]?.goal_quantity ?? null

    if (achievedQuantity === null || goalQuantity === null) {
        return 'bg-neutral-900 bg-opacity-80'
    }

    if (achievedQuantity >= goalQuantity) {
        return 'bg-primary bg-opacity-80 text-inverted'
    }

    if (achievedQuantity > 0) {
        return 'bg-neutral-900 bg-opacity-80 border-2 border-primary shadow-primary shadow-[inset_0_0_5px]'
    }

    return 'bg-neutral-900 bg-opacity-80'
}

const setMonthDays = () => {
    weeksOfMonth.value = []

    const calendarStart = month.clone().startOf('month').startOf('week')
    const calendarEnd = month.clone().endOf('month').endOf('week')

    let currentDate = calendarStart.clone()

    while (currentDate.isBefore(calendarEnd)) {
        if (weeksOfMonth.value.length === 0 || currentDate.day() === 1) {
            weeksOfMonth.value.push({
                week: currentDate.week(),
                days: [],
            })
        }

        let weekIndex = weeksOfMonth.value.length - 1
        weeksOfMonth.value[weekIndex]?.days.push({
            day: currentDate.format('D'),
            date: currentDate.format('YYYY-MM-DD'),
            outsideMonth: currentDate.month() !== month.month(),
        })

        currentDate.add(1, 'days')
    }
}

watch(
    () => month,
    () => {
        setMonthDays()
    }
)

onMounted(() => {
    setMonthDays()
})
</script>

<template>
    <UCard class="w-full max-w-[700px]">
        <template #default>
            <div class="w-full flex flex-wrap">
                <div class="w-full font-bold text-xl">
                    {{ month.format('Y MMMM') }}
                </div>

                <div class="w-full flex flex-wrap justify-evenly">
                    <div
                        class="m-0.5 my-0.5 md:m-1 md:my-2 w-8 h-8 md:w-12 md:h-12 text-sm md:text-sm flex justify-center items-center rounded-full"
                        v-for="day in 7"
                    >
                        {{
                            moment()
                                .startOf('week')
                                .add(day - 1, 'days')
                                .format('ddd')
                        }}
                    </div>
                </div>
                <div
                    class="w-full flex flex-wrap justify-evenly"
                    v-for="(week, weekIndex) in weeksOfMonth"
                    :key="weekIndex"
                >
                    <div
                        v-for="(day, dayIndex) in week.days"
                        :key="dayIndex"
                        class="m-0.5 my-0.5 md:m-1 md:my-2 w-8 h-8 md:w-12 md:h-12 text-sm md:text-sm flex justify-center items-center select-none rounded-full"
                        :class="[day.outsideMonth ? '' : getDayColorClasses(day)]"
                    >
                        <!-- Achieved quantity text -->
                        <span
                            v-if="!day.outsideMonth && isGoalType(selectedGoal)"
                            class="hidden md:block"
                        >
                            {{
                                calendar.goals[selectedGoal]?.goalAchievements[day.date]
                                    ?.achieved_quantity ?? '-'
                            }}
                        </span>

                        <!-- Reviews due text -->
                        <span
                            v-if="!day.outsideMonth && !isGoalType(selectedGoal)"
                            class="hidden md:block"
                        >
                            {{ calendar.reviews[day.date]?.quantity ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </UCard>
</template>

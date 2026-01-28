<script setup lang="ts">
import { isGoalType } from '@lctypes/goals/Goal'

import type { CalendarDay } from '@lctypes/calendar/CalendarDay'
import type { Calendar } from '@lctypes/calendar/Calendar'
import { CalendarSelectableStatEnum } from '@lctypes/calendar/Calendar'

type Props = {
    calendarData: Calendar
    selectedGoal: string
    day: CalendarDay
    mostDueReviews: number
}

const { calendarData, selectedGoal, day, mostDueReviews } = defineProps<Props>()

const getAchievedQuantity = (day: CalendarDay): number | null => {
    if (isGoalType(selectedGoal)) {
        return (
            calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.achieved_quantity ?? null
        )
    }

    if (selectedGoal == CalendarSelectableStatEnum.ReviewsDue) {
        return calendarData.reviews[day.date]?.quantity ?? null
    }

    return null
}

const getGoalQuantity = (day: CalendarDay): number | null => {
    if (isGoalType(selectedGoal)) {
        return calendarData.goals['read_words']?.goalAchievements[day.date]?.goal_quantity ?? null
    }

    if (selectedGoal === CalendarSelectableStatEnum.ReviewsDue) {
        return mostDueReviews
    }

    return null
}

const getDayColorClasses = (day: CalendarDay): string => {
    let achievedQuantity = getAchievedQuantity(day)
    let goalQuantity = getGoalQuantity(day)

    if (achievedQuantity === null || goalQuantity === null) {
        return 'bg-neutral-900'
    }

    if (achievedQuantity >= goalQuantity) {
        return 'bg-primary text-inverted'
    }

    if (achievedQuantity > 0) {
        return 'bg-neutral-900 bg-gradient-to-tr from-primary-300 from-0% via-primary-400 via-25% to-transparent to-25%'
    }

    return 'bg-neutral-900'
}

const getOpacityStyle = (day: CalendarDay): string => {
    let achievedQuantity = getAchievedQuantity(day)
    let goalQuantity = getGoalQuantity(day)

    if (achievedQuantity === null || goalQuantity === null) {
        return 'opacity: 100%'
    }
    if (achievedQuantity >= goalQuantity) {
        return 'opacity: 100%'
    }
    if (achievedQuantity > 0) {
        return 'opacity: 100%'
    }

    return 'opacity: 100%'
}

const getStyleClasses = (day: CalendarDay): string[] => {
    return [
        day.outsideMonth ? '' : getDayColorClasses(day),
        'm-0.5 my-0.5 md:m-1 w-8 h-8 md:w-9 md:h-9 text-sm flex justify-center items-center select-none rounded-sm md:rounded-md',
    ]
}
</script>

<template>
    <UPopover>
        <template #content>
            <CalendarEditPopover :calendar-data="calendarData" :day="day" />
        </template>
        <div :class="getStyleClasses(day)" :style="getOpacityStyle(day)">
            <!-- Achieved quantity text -->
            <span v-if="!day.outsideMonth && isGoalType(selectedGoal)">
                <!-- {{
                    calendarData.goals[selectedGoal as GoalType]?.goalAchievements[day.date]
                    ?.achieved_quantity ?? '-'
                    }} -->
                <span class="text-xs">{{ day.day }}</span>
            </span>

            <!-- Reviews due text -->
            <span
                v-if="!day.outsideMonth && selectedGoal === CalendarSelectableStatEnum.ReviewsDue"
            >
                {{ calendarData.reviews[day.date]?.quantity ?? '-' }}
            </span>

            <!-- <span v-if="day.outsideMonth && !isGoalType(selectedGoal)" class="hidden md:block">
                    {{ calendarData.reviews[day.date]?.quantity ?? '-' }}
                </span> -->
        </div>
    </UPopover>
</template>

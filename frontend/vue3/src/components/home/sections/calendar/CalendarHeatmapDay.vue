<script setup lang="ts">
import { isGoalType } from '@lctypes/goals/Goal'

import type { CalendarDay } from '@lctypes/calendar/CalendarDay'
import type { Calendar } from '@lctypes/calendar/Calendar'
import { CalendarSelectableStatEnum } from '@lctypes/calendar/Calendar'
import { formatGoalType } from '@src/helpers/GoalHelper'

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

    if (selectedGoal === CalendarSelectableStatEnum.ReviewsDue) {
        return calendarData.reviews[day.date]?.quantity ?? null
    }

    return null
}

const getGoalQuantity = (day: CalendarDay): number | null => {
    if (isGoalType(selectedGoal)) {
        return calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.goal_quantity ?? null
    }

    if (selectedGoal === CalendarSelectableStatEnum.ReviewsDue) {
        return mostDueReviews
    }

    return null
}

const getBgColor = (day: CalendarDay): string => {
    let achievedQuantity = getAchievedQuantity(day)

    if (!achievedQuantity) {
        return `bg-elevated`
    }

    return 'bg-primary'
}

const getOpacityStyle = (day: CalendarDay): string => {
    let achievedQuantity = getAchievedQuantity(day)
    let goalQuantity = getGoalQuantity(day)

    if (day.outsideYear) {
        return 'opacity: 0%'
    }

    if (achievedQuantity === 0 || achievedQuantity === null) {
        return 'opacity: 100%'
    }

    let percentage = '0%'
    if (achievedQuantity !== null && goalQuantity) {
        percentage = ((achievedQuantity / goalQuantity) * 100).toFixed(2) + '%'
    }

    return `opacity: ${percentage};`
}

const getDayTooltip = (day: CalendarDay): string => {
    let tooltipText = day.date
    let achievedQuantity = getAchievedQuantity(day)

    tooltipText += '<br>'
    tooltipText += achievedQuantity ? String(achievedQuantity) : 'no'

    tooltipText += ' ' + formatGoalType(selectedGoal)

    return tooltipText
}
</script>

<template>
    <UPopover arrow>
        <template #content>
            <CalendarEditPopover :calendar-data="calendarData" :day="day" />
        </template>
        <UTooltip
            arrow
            :content="{ side: 'top', sideOffset: 4 }"
            :disabled="day.outsideYear"
            :delay-duration="0"
            :ui="{
                content: 'h-full',
            }"
        >
            <template #content>
                <div class="" v-html="getDayTooltip(day)"></div>
            </template>

            <div
                :class="[
                    'm-[1px] w-[17px] h-[17px] text-xs md:text-sm flex justify-center items-center select-none rounded-xs hover:bg-success',
                    getBgColor(day),
                ]"
                :style="getOpacityStyle(day)"
            ></div>
        </UTooltip>
    </UPopover>
</template>

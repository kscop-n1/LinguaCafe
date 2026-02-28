<script setup lang="ts">
import { isGoalType } from '@lctypes/goals/Goal'
import { computed } from 'vue'
import Store from '@src/store/Store'

import { CalendarSelectableStatEnum } from '@lctypes/calendar/Calendar'
import type { CalendarDay } from '@lctypes/calendar/CalendarDay'
import type { Calendar } from '@lctypes/calendar/Calendar'
import { formatGoalType } from '@src/helpers/GoalHelper'

type Props = {
    calendarData: Calendar
    selectedGoal: string
    day: CalendarDay
    mostDueReviews: number
}

const { calendarData, selectedGoal, day } = defineProps<Props>()

const isGoalAchieved = computed<boolean>(() => {
    if (!isGoalType(selectedGoal)) {
        return false
    }

    let achievedQuantity = calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.achieved_quantity ?? 0
    let goalQuantity = calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.goal_quantity ?? 0

    return achievedQuantity >= goalQuantity
})

const goalQuantity = computed<number>(() => {
    if (!isGoalType(selectedGoal)) {
        return 0
    }

    let goalQuantity = calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.goal_quantity ?? 0

    return goalQuantity
})

const achievedGoalQuantity = computed<number>(() => {
    if (!isGoalType(selectedGoal)) {
        return 0
    }

    let achievedQuantity = calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.achieved_quantity ?? 0

    return achievedQuantity
})

const achievedGoalAdjustedToMax = computed<number>(() => {
    if (!isGoalType(selectedGoal)) {
        return 0
    }

    let achievedQuantity = calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.achieved_quantity ?? 0
    let goalQuantity = calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.goal_quantity ?? 0

    return achievedQuantity >= goalQuantity ? goalQuantity : achievedQuantity
})

const getDayTooltip = () => {
    if (!calendarData) {
        return ''
    }


    return achievedGoalQuantity.value + ' / ' + goalQuantity.value + ' ' + formatGoalType(selectedGoal)
}
</script>

<template>
    <UPopover>
        <template #content>
            <CalendarEditPopover :calendar-data="calendarData" :day="day" />
        </template>
        <div :class="[
            day.outsideMonth ? '' : 'bg-elevated',
            isGoalAchieved && !day.outsideMonth ? 'border-1 border-success/0' : 'border-1 border-transparent',
            'flex justify-center items-center mx-0.5 md:mx-1 flex-1 md:h-20 select-none rounded-sm',
        ]">
            <!-- Achieved quantity text -->
            <div class="w-full h-full p-1 sm:p-2 flex flex-col justify-between" v-if="!day.outsideMonth && isGoalType(selectedGoal)">
                <div class="w-full font-bold text-xs sm:text-base mb-1">{{ day.day }}</div>
                <template v-if="calendarData.goals[selectedGoal]?.goalAchievements[day.date]">
                    <!-- {{ calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.achieved_quantity }}
                    {{ calendarData.goals[selectedGoal]?.goalAchievements[day.date]?.goal_quantity }} -->
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
                            <div class="" v-html="getDayTooltip()"></div>
                        </template>
                        <UProgress 
                            :model-value="achievedGoalAdjustedToMax"
                            :max="goalQuantity"
                            :color="isGoalAchieved ? 'success' : 'primary'"
                            :size="Store.window.width < 460 ? 'sm' : 'md'"
                        />
                     </UTooltip>
                </template>
            </div>

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

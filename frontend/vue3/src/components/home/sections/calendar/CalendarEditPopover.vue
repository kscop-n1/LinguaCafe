<script setup lang="ts">
import { CalendarSelectableStatEnum } from '@lctypes/calendar/Calendar'
import { formatGoalType } from '@src/helpers/GoalHelper'

import type { CalendarDay } from '@lctypes/calendar/CalendarDay'
import type { Calendar } from '@lctypes/calendar/Calendar'

type Props = {
    calendarData: Calendar
    day: CalendarDay
}

const { calendarData, day } = defineProps<Props>()

calendarData
day
</script>

<template>
    <div class="p-4">
        <div v-for="(goal, goalIndex) in Object.values(CalendarSelectableStatEnum)" :key="goalIndex">
            {{ formatGoalType(goal) }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onBeforeMount } from 'vue'
import GoalService from '@services/goals/GoalService'

import type { Goal } from '@lctypes/goals/Goal'

const goalService = new GoalService()
const goals = ref<Goal[]>([])

onBeforeMount(async () => {
    const goalResponse = await goalService.getGoals()

    if (goalResponse.ok && goalResponse.data) {
        goals.value = goalResponse.data
    }
})
</script>

<template>
    <div class="mb-8">
        <div v-for="(goal, goalIndex) in goals" :key="goalIndex">
            <div class="flex items-center">
                <div class="w-full shrink">
                    <div class="flex justify-between text-sm text-tuned mb-0.5">
                        <div>{{ goal.name }}</div>
                        <div>{{ goal.todays_quantity }} / {{ goal.quantity }}</div>
                    </div>
                    <UProgress
                        v-model="goal.todays_quantity"
                        class="mb-4"
                        :max="goal.quantity"
                        :color="
                            (goal.todays_quantity ?? 0) >= (goal.quantity ?? 0)
                                ? 'success'
                                : 'primary'
                        "
                        size="lg"
                    />
                </div>
                <UIcon class="size-5 ml-4 cursor-pointer" name="i-lucide-pen" />
            </div>
        </div>
    </div>
</template>

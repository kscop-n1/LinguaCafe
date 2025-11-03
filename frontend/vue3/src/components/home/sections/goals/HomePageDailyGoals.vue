<script setup lang="ts">
import { ref, onBeforeMount } from 'vue'
import GoalService from '@services/goals/GoalService'
import EditGoalPopup from '@components/popups/goals/EditGoalPopup.vue'

import type { Goal } from '@lctypes/goals/Goal'

const loading = ref<boolean>(false)
const editedGoal = ref<null | Goal>(null)
const showEditGoalPopup = ref<boolean>(false)

const goalService = new GoalService()
const goals = ref<Goal[]>([])

const openEditGoalPopup = function (goal: Goal) {
    editedGoal.value = goal
    showEditGoalPopup.value = true
}

const loadGoals = async function () {
    loading.value = true
    const goalResponse = await goalService.getGoals()
    loading.value = false

    if (goalResponse.ok && goalResponse.data) {
        goals.value = goalResponse.data
    }
}

onBeforeMount(async function () {
    loadGoals()
})
</script>

<template>
    <div>
        <EditGoalPopup
            v-if="showEditGoalPopup && editedGoal"
            v-model="showEditGoalPopup"
            :goal="editedGoal"
            @goal-changed="loadGoals"
            @updated="loadGoals"
        />

        <div v-for="(goal, goalIndex) in goals" :key="goalIndex">
            <div class="flex items-center">
                <div class="w-full shrink">
                    <div class="flex justify-between text-sm text-tuned mb-0.5">
                        <template v-if="loading">
                            <USkeleton class="h-4 w-32" />
                            <div class="flex">
                                <USkeleton class="h-4 w-16 mr-1" /> /
                                <USkeleton class="h-4 w-16 ml-1" />
                            </div>
                        </template>

                        <template v-else>
                            <div>{{ goal.name }}</div>
                            <div>{{ goal.todays_quantity }} / {{ goal.quantity }}</div>
                        </template>
                    </div>
                    <UProgress
                        v-model="goal.todays_quantity"
                        class="mb-4"
                        :max="
                            goal.quantity &&
                            goal.todays_quantity &&
                            goal.quantity > goal.todays_quantity
                                ? goal.quantity
                                : goal.todays_quantity
                        "
                        :color="
                            (goal.todays_quantity ?? 0) >= (goal.quantity ?? 0)
                                ? 'success'
                                : 'primary'
                        "
                        size="lg"
                    />
                </div>

                <UButton
                    class="ml-1"
                    :icon="loading ? '' : 'i-lucide-pen'"
                    size="md"
                    color="primary"
                    variant="ghost"
                    @click="openEditGoalPopup(goal)"
                    loading-icon="i-lucide-loader-circle"
                    :loading="loading"
                />
            </div>
        </div>
    </div>
</template>

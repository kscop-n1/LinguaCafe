<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import * as zod from 'zod'
import GoalService from '@services/goals/GoalService'

import type { FormError } from '@nuxt/ui'
import type { Goal } from '@lctypes/goals/Goal'

const goalService = new GoalService()

// modal dialog
type Props = {
    modelValue: boolean
    goal: Goal
}

const { modelValue, goal } = defineProps<Props>()
const modalOpened = computed({
    get: () => modelValue,
    set: value => emit('update:modelValue', value),
})

const emit = defineEmits(['update:modelValue', 'updated'])

// form
const editGoalErrors = ref<FormError[]>([])
const editGoalFormSchema = zod.object({
    quantity: zod.number('Must be a number').gte(0, { message: 'Must be greater or equal to 0' }),
})

type Schema = zod.output<typeof editGoalFormSchema>

const editGoalFormState = reactive<Partial<Schema>>({
    quantity: goal.quantity,
})

const updateGoalQuantity = async function () {
    const goalSericeUpdateResult = await goalService.updateGoal(
        goal.id,
        editGoalFormState.quantity as number
    )

    if (goalSericeUpdateResult.ok) {
        modalOpened.value = false
        emit('updated')
    }

    if (!goalSericeUpdateResult.ok && goalSericeUpdateResult.errorMessages) {
        editGoalErrors.value = goalSericeUpdateResult.errorMessages
    }
}
</script>

<template>
    <UForm
        id="edit-goal-form"
        :schema="editGoalFormSchema"
        :state="editGoalFormState"
        :validate-on="['change', 'input']"
        @submit="updateGoalQuantity"
        v-slot="{ loading }"
    >
        <UModal
            class="w-full max-w-lg"
            variant=""
            title="Edit goal"
            v-model:open="modalOpened"
            :dismissible="false"
            :close="!loading"
        >
            <template #body>
                <div class="p-4">
                    <UAlert
                        class="mb-4"
                        color="primary"
                        icon="i-lucide-circle-alert"
                        description="This setting will only affect today's and upcoming days' goal. Past days' goals can be modified by clicking on the calendar day."
                    >
                    </UAlert>

                    <UFormField label="Goal quantity" name="quantity" required>
                        <UInputNumber
                            v-model="editGoalFormState.quantity"
                            size="lg"
                            variant="subtle"
                            class="w-full"
                            autofocus
                            required
                        />
                    </UFormField>
                </div>

                <FormResponseErrorAlert
                    class="mt-4"
                    :title="'Error'"
                    :error-messages="editGoalErrors"
                />
            </template>
            <template #footer>
                <div class="w-full mt-4 flex justify-end">
                    <UButton
                        class="justify-center font-normal mr-2"
                        label="Cancel"
                        type="button"
                        variant="link"
                        color="neutral"
                        @click="modalOpened = false"
                        :disabled="loading"
                    />

                    <UButton
                        class="justify-center font-normal"
                        label="Save"
                        type="submit"
                        form="edit-goal-form"
                        loading-icon="i-lucide-loader-circle"
                        loading-auto
                    />
                </div>
            </template>
        </UModal>
    </UForm>
</template>

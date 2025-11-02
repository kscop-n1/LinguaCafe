<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import * as zod from 'zod'
import UserService from '@services/users/UserService'

import type { FormError } from '@nuxt/ui'

const userService = new UserService()

// modal dialog
type Props = {
    modelValue: boolean
    firstUser: boolean
}

const { modelValue, firstUser } = defineProps<Props>()
const modalOpened = computed({
    get: () => modelValue,
    set: value => emit('update:modelValue', value),
})

const emit = defineEmits(['update:modelValue'])

// form
const createUserErrors = ref<FormError[]>([])
const showPassword = ref<boolean>(false)
const createUserFormSchema = zod
    .object({
        name: zod
            .string('Name is required')
            .min(4, 'Name must be at least 4 characters')
            .max(255, 'Name must be below 255 characters'),
        email: zod.email('Invalid email'),
        password: zod
            .string('Password is required')
            .min(8, 'Must be between 8 and 32 characters.')
            .max(32, 'Must be between 8 and 32 characters.'),
        passwordConfirmation: zod.string('Password is required'),
        isAdmin: zod.boolean(),
    })
    .refine(data => data.password === data.passwordConfirmation, {
        message: 'Passwords do not match',
        path: ['passwordConfirmation'],
    })

type Schema = zod.output<typeof createUserFormSchema>

const createUserFormState = reactive<Partial<Schema>>({
    name: undefined,
    email: undefined,
    password: undefined,
    passwordConfirmation: undefined,
    isAdmin: true,
})

const createUser = async function () {
    const createUserResponse = await userService.createUser(
        createUserFormState.name,
        createUserFormState.email,
        createUserFormState.password,
        createUserFormState.passwordConfirmation,
        createUserFormState.isAdmin
    )

    if (!createUserResponse.ok && createUserResponse.errorMessages) {
        createUserErrors.value = createUserResponse.errorMessages
    }

    if (createUserResponse.ok) {
        modalOpened.value = false
    }
}
</script>

<template>
    <UModal
        class="w-96"
        variant=""
        title="Create user"
        v-model:open="modalOpened"
        :dismissible="false"
    >
        <template #body>
            <div class="p-4">
                <UForm
                    id="create-user-form"
                    :schema="createUserFormSchema"
                    :state="createUserFormState"
                    :validate-on="['change', 'input']"
                    @submit="createUser"
                >
                    <UFormField label="Name" name="name" required>
                        <UInput
                            v-model="createUserFormState.name"
                            size="lg"
                            variant="subtle"
                            class="w-full"
                            autofocus
                            required
                        />
                    </UFormField>

                    <UFormField class="mt-4" label="E-mail" name="email" required>
                        <UInput
                            v-model="createUserFormState.email"
                            size="lg"
                            variant="subtle"
                            class="w-full"
                            required
                        />
                    </UFormField>

                    <UFormField class="mt-4" label="Password" name="password" required>
                        <UInput
                            v-model="createUserFormState.password"
                            size="lg"
                            variant="subtle"
                            class="w-full"
                            required
                            :type="showPassword ? 'text' : 'password'"
                        >
                            <template #trailing>
                                <UTooltip :text="showPassword ? 'Hide password' : 'Show password'">
                                    <UButton
                                        color="neutral"
                                        variant="link"
                                        size="sm"
                                        :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                        :aria-label="
                                            showPassword ? 'Hide password' : 'Show password'
                                        "
                                        :aria-pressed="showPassword"
                                        aria-controls="password"
                                        @click="showPassword = !showPassword"
                                    />
                                </UTooltip>
                            </template>
                        </UInput>
                    </UFormField>

                    <UFormField
                        class="mt-4"
                        label="Password confirmation"
                        name="passwordConfirmation"
                        required
                    >
                        <UInput
                            v-model="createUserFormState.passwordConfirmation"
                            size="lg"
                            variant="subtle"
                            class="w-full"
                            required
                            :type="showPassword ? 'text' : 'password'"
                        >
                            <template #trailing>
                                <UTooltip :text="showPassword ? 'Hide password' : 'Show password'">
                                    <UButton
                                        color="neutral"
                                        variant="link"
                                        size="sm"
                                        :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                        :aria-label="
                                            showPassword ? 'Hide password' : 'Show password'
                                        "
                                        :aria-pressed="showPassword"
                                        aria-controls="password"
                                        @click="showPassword = !showPassword"
                                    />
                                </UTooltip>
                            </template>
                        </UInput>
                    </UFormField>

                    <UFormField class="mt-4" label="User role" name="isAdmin" required>
                        <USwitch
                            v-model="createUserFormState.isAdmin"
                            label="Admin"
                            :disabled="firstUser"
                        />
                    </UFormField>
                </UForm>
            </div>

            <FormResponseErrorAlert
                class="mt-4"
                :title="'Error'"
                :error-messages="createUserErrors"
            />
        </template>
        <template #footer>
            <div class="w-full mt-4 flex justify-end">
                <UButton
                    class="justify-center font-normal mr-2"
                    label="Cancel"
                    variant="link"
                    color="neutral"
                    type="button"
                    @click="modalOpened = false"
                />

                <UButton
                    class="justify-center font-normal"
                    label="Create user"
                    type="submit"
                    form="create-user-form"
                    loading-icon="i-lucide-loader-circle"
                    loading-auto
                />
            </div>
        </template>
    </UModal>
</template>

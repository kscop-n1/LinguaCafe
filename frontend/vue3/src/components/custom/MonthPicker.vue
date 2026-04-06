<script setup lang="ts">
import { ref, onMounted } from 'vue'

import type { Moment } from 'moment'
import moment from 'moment'

type Props = {
    value: Moment
}

const { value = moment().startOf('year') } = defineProps<Props>()

const emit = defineEmits(['update:value'])

const opened = ref<boolean>(false)
const selectedDate = ref<Moment>(moment().startOf('month'))
const monthPickerYear = ref<Moment>(moment().startOf('year'))
const months = ref<string[]>([
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
])

const close = (): void => {
    opened.value = false
}

// reset year to original when closed
// watch(opened, newValue => {
//     if (!newValue) {
//         return
//     }

//     monthPickerYear.value = moment().startOf('year')
// })

const selectMonth = (month: string): void => {
    selectedDate.value.year(monthPickerYear.value.year())
    selectedDate.value.month(month)

    selectedDate.value = selectedDate.value.clone()
    emit('update:value', selectedDate.value)
    close()
}

const previousYear = () => {
    monthPickerYear.value = monthPickerYear.value.clone().subtract(12, 'months')
}

const nextYear = () => {
    monthPickerYear.value = monthPickerYear.value.clone().add(12, 'months')
}

onMounted(() => {
    monthPickerYear.value = value
})
</script>

<template>
    <UPopover v-model:open="opened">
        <UButton
            class="w-36 d-flex justify-center"
            color="neutral"
            variant="subtle"
            icon="i-lucide-calendar"
        >
            {{ selectedDate.format('YYYY - MMM') }}
        </UButton>

        <template #content>
            <div class="w-full p-4">
                <div class="w-full flex justify-around mb-2">
                    <div
                        class="flex items-center text-white rounded-xl hover:bg-elevated py-1 px-4 m-0"
                        @click="previousYear"
                    >
                        <UIcon name="i-lucide-arrow-big-left" />
                    </div>
                    <div class="py-1 px-2 m-0 select-none">
                        {{ monthPickerYear.format('YYYY') }}
                    </div>
                    <div
                        class="flex items-center text-white rounded-xl hover:bg-elevated py-1 px-4 m-0"
                        @click="nextYear"
                    >
                        <UIcon name="i-lucide-arrow-big-right" />
                    </div>
                </div>
                <div>
                    <div
                        class="py-1 px-4 select-none hover:bg-elevated rounded-xl text-sm"
                        v-for="(month, monthIndex) in months"
                        :key="monthIndex"
                        @click="selectMonth(month)"
                    >
                        {{ month }}
                    </div>
                </div>
            </div>
        </template>
    </UPopover>
</template>

import { inject } from 'vue'
import moment from 'moment'

export function useMoment() {
    const momentInstance = inject<typeof moment>('moment')

    if (!momentInstance) {
        throw new Error('Moment not provided!')
    }

    return momentInstance
}

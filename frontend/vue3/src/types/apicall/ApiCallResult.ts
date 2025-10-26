import type { FormError } from '@nuxt/ui'

export type ApiCallResult<T> = {
    ok: boolean
    status: null | number
    data?: T
    error?: any
    errorMessages?: null | FormError[]
}

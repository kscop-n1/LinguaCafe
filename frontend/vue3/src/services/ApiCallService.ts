import type { FormError } from '@nuxt/ui'
import type { LaravelValidationErrors } from '@src/types/apicall/LaravelValidationErrors'

export default class ApiCallService {
    getValidationErrors(axiosError: any): FormError[] {
        let errors: FormError[] = []

        let validationErrors: null | LaravelValidationErrors
        validationErrors = (axiosError?.response?.data?.errors as LaravelValidationErrors) ?? null

        if (!validationErrors) {
            return errors
        }

        Object.entries(validationErrors).forEach(errorField => {
            errorField[1].forEach(error => {
                errors.push({
                    name: errorField[0],
                    message: error,
                })
            })
        })

        return errors
    }
}

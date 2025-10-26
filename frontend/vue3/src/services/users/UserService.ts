import axios from 'axios'
import store from '@store/Store'
import ApiCallService from '@services/ApiCallService'

import type { User } from '@lctypes/User.ts'
import type { ApiCallResult } from '@src/types/apicall/ApiCallResult'

export default class AuthService {
    apiCallService: ApiCallService
    toastService: ReturnType<typeof useToast>

    constructor() {
        this.apiCallService = new ApiCallService()
        this.toastService = useToast()
    }

    async createUser(
        name: undefined | string,
        email: undefined | string,
        password: undefined | string,
        passwordConfirmation: undefined | string,
        isAdmin: undefined | boolean
    ): Promise<ApiCallResult<User>> {
        try {
            const response = await axios<User>({
                method: 'POST',
                url: '/api/users/store',
                data: {
                    name: name,
                    email: email,
                    password: password,
                    password_confirmation: passwordConfirmation,
                    isAdmin: isAdmin,
                },
            })

            store.hasUser = true
            this.toastService.add({
                title: 'User creation',
                description: `User has been successfully created: ${email}.`,
                icon: 'i-lucide-triangle-alert',
                color: 'success',
                duration: 10000,
            })

            return {
                ok: true,
                status: response.status,
            }
        } catch (error: any) {
            return {
                ok: false,
                error: error ?? null,
                errorMessages: this.apiCallService.getErrorMessages(error),
                status: error?.response?.status ?? null,
            }
        }
    }
}

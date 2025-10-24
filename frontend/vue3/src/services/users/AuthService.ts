import axios from 'axios'
import Store from '@store/Store'
import ApiCallService from '@services/ApiCallService'
import { useRouter } from 'vue-router'

import type { User } from '@lctypes/User.ts'
import type { ApiCallResult } from '@lctypes/ApiCall/ApiCallResult'
import type { Router } from 'vue-router'

export default class AuthService {
    apiCallService: ApiCallService
    router: Router

    constructor() {
        this.apiCallService = new ApiCallService()
        this.router = useRouter()
    }

    async login(
        email: undefined | string,
        password: undefined | string,
        remember: undefined | boolean
    ): Promise<ApiCallResult<User>> {
        try {
            const response = await axios<User>({
                method: 'POST',
                url: '/api/auth/login',
                data: {
                    email: email,
                    password: password,
                    remember: remember,
                },
            })

            Store.user = response.data
            this.router.push('/')

            return {
                ok: true,
                data: Store.user,
                status: response.status,
            }
        } catch (error: any) {
            if (axios.isAxiosError(error)) {
                return {
                    ok: false,
                    error: error,
                    validationErrors: this.apiCallService.getValidationErrors(error),
                    status: error.response?.status ?? null,
                }
            }

            return {
                ok: false,
                error: null,
                validationErrors: this.apiCallService.getValidationErrors(error),
                status: error?.response?.status ?? null,
            }
        }
    }

    async logout(): Promise<ApiCallResult<User>> {
        try {
            const response = await axios<User>({
                method: 'POST',
                url: '/api/auth/logout',
            })

            Store.user = null
            Store.hasUser = true
            Store.language = null

            this.router.push('/login')

            return {
                ok: true,
                status: response.status,
            }
        } catch (error: any) {
            return {
                ok: false,
                status: error?.response?.status ?? null,
            }
        }
    }
}

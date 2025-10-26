import axios from 'axios'
import store from '@store/Store'
import ApiCallService from '@services/ApiCallService'
import { useRouter } from 'vue-router'

import type { User } from '@lctypes/User.ts'
import type { ApiCallResult } from '@src/types/apicall/ApiCallResult'
import type { LaravelResource } from '@lctypes/apicall/LaravelResource'
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
            const response = await axios<LaravelResource<User>>({
                method: 'POST',
                url: '/api/auth/login',
                data: {
                    email: email,
                    password: password,
                    remember: remember,
                },
            })

            store.user = response.data.data
            this.router.push('/')

            return {
                ok: true,
                data: store.user,
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

    async logout(): Promise<ApiCallResult<User>> {
        try {
            const response = await axios<User>({
                method: 'POST',
                url: '/api/auth/logout',
            })

            store.user = null
            store.hasUser = true
            store.language = null

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

import axios from 'axios'
import store from '@store/Store'
import ApiCallService from '@services/ApiCallService'

import type { User } from '@lctypes/User.ts'
import type { ApiCallResult } from '@src/types/apicall/ApiCallResult'

export default class AppService {
    apiCallService: ApiCallService
    toastService: ReturnType<typeof useToast>

    constructor() {
        this.apiCallService = new ApiCallService()
        this.toastService = useToast()
    }

    async initializeStore(): Promise<ApiCallResult<User>> {
        try {
            const response = await axios({
                method: 'GET',
                url: '/api/users/data',
            })

            store.user = response.data.user ?? null
            store.hasUser = response.data.userCount > 0
            store.language = response.data.user?.selected_language ?? null
            store.appDataInitialized = true

            return {
                ok: true,
                status: response.status,
            }
        } catch (error: any) {
            this.toastService.add({
                title: 'Error',
                description: 'An error has occurred while loading initial data for the app.',
                icon: 'i-lucide-triangle-alert',
                color: 'error',
                duration: 10000,
            })

            return {
                ok: false,
                status: error?.response?.status ?? null,
            }
        }
    }
}

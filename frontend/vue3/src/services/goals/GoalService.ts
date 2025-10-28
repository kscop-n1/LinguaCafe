import axios from 'axios'
import ApiCallService from '@services/ApiCallService'

import type { ApiCallResult } from '@src/types/apicall/ApiCallResult'
import type { Goal } from '@lctypes/goals/Goal'
import type { LaravelResource } from '@lctypes/apicall/LaravelResource'

export default class GoalService {
    apiCallService: ApiCallService

    constructor() {
        this.apiCallService = new ApiCallService()
    }

    async getGoals(): Promise<ApiCallResult<Goal[]>> {
        try {
            const response = await axios<LaravelResource<Goal[]>>({
                method: 'GET',
                url: '/api/goals',
            })

            return {
                ok: true,
                data: response.data.data,
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

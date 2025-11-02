import axios from 'axios'
import ApiCallService from '@services/ApiCallService'

import type { Statistics } from '@lctypes/statistics/Statistics'
import type { ApiCallResult } from '@src/types/apicall/ApiCallResult'
import type { LaravelResource } from '@lctypes/apicall/LaravelResource'

export default class StatisticsService {
    apiCallService: ApiCallService

    constructor() {
        this.apiCallService = new ApiCallService()
    }

    async getStatistics(): Promise<ApiCallResult<Statistics>> {
        try {
            const response = await axios<LaravelResource<Statistics>>({
                method: 'GET',
                url: '/api/statistics',
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

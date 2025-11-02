import axios from 'axios'
import ApiCallService from '@services/ApiCallService'

import type { ApiCallResult } from '@src/types/apicall/ApiCallResult'
import type { Goal } from '@lctypes/goals/Goal'
import type { LaravelResource } from '@lctypes/apicall/LaravelResource'

export default class GoalService {
    apiCallService: ApiCallService
    toastService: ReturnType<typeof useToast>

    constructor() {
        this.apiCallService = new ApiCallService()
        this.toastService = useToast()
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

    async updateGoal(goalId: number, newGoalQuantity: number): Promise<ApiCallResult<Goal[]>> {
        try {
            const response = await axios({
                method: 'POST',
                url: `/api/goals/${goalId}`,
                data: {
                    newGoalQuantity: newGoalQuantity,
                },
            })

            this.toastService.add({
                title: 'Goal editing',
                description: `A goal quantity has been successfully edited.`,
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

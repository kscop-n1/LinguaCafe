import type { Language } from '@lctypes/Language'
import type { GoalAchievement } from './GoalAchievement'

export enum GoalType {
    Review = 'review',
    ReadWords = 'read_words',
    LearnWords = 'learn_words',
}

export type Goal = {
    id: number
    name?: string
    user_id?: number
    language?: Language
    type?: GoalType
    target_id?: null | number
    // TODO: check if current_chapter is still needed, probably deletable
    current_chapter?: null | number
    quantity?: number
    goalAchievements: Record<string, GoalAchievement>
    updated_at?: string
    created_at?: string
    todays_quantity?: number
}

export function isGoalType(value: string): value is GoalType {
    return Object.values(GoalType).includes(value as GoalType)
}

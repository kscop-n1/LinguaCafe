import type { Goal, GoalType } from '@lctypes/goals/Goal'
import type { CalendarReview } from './CalendarReview'

export enum CalendarSelectableStatEnum {
    Review = 'review',
    ReadWords = 'read_words',
    LearnWords = 'learn_words',
    ReviewsDue = 'reviews_due',
}

export type Calendar = {
    goals: Record<GoalType, Goal>
    reviews: Record<string, CalendarReview>
}

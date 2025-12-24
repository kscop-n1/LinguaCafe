import type { Goal, GoalType } from '@lctypes/goals/Goal'
import type { CalendarReview } from './CalendarReview'

export type Calendar = {
    goals: Record<GoalType, Goal>
    reviews: Record<string, CalendarReview>
}

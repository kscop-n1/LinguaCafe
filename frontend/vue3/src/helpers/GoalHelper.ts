import { CalendarSelectableStatEnum } from '@lctypes/calendar/Calendar'
import { GoalType } from '@lctypes/goals/Goal'

export function formatGoalType(value: GoalType | CalendarSelectableStatEnum): string | null {
    switch (value) {
        case GoalType.LearnWords:
            return 'learned words'
            break
        case GoalType.ReadWords:
            return 'read words'
            break
        case GoalType.Review:
            return 'reviews'
            break

        case CalendarSelectableStatEnum.ReviewsDue:
            return 'reviews due'
            break
    }

    return null
}

import type { Statistic } from '@lctypes/statistics/Statistic'

export type Statistics = {
    days: Statistic
    learning: Statistic
    known: Statistic
    knownLemmas: Statistic
    readWordCounts: Statistic
}

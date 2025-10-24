import type { User } from '@lctypes/User'
import type { Language } from '@lctypes/Language'
import type { Settings } from '@lctypes/settings/Settings'

export type Store = {
    user: null | User
    hasUser: boolean
    language: null | Language
    settings: Settings
}

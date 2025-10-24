import type { User } from '@lctypes/User'
import type { Language } from '@lctypes/Language'

export type Store = {
    user: null | User
    hasUser: boolean
    language: null | Language
}

import type { Language } from '@lctypes/Language'

export type User = {
    name: string
    email: string
    is_admin: boolean
    password_changed: boolean
    selected_language: Language
}

import type { User } from '@lctypes/User'
import type { Language } from '@lctypes/Language'
import type { Settings } from '@lctypes/settings/Settings'
import type { WindowData } from '@lctypes/store/WindowData'

export type Store = {
    appDataInitialized: boolean
    hasUser: boolean
    language: null | Language
    sidebarCollapsed: boolean
    settings: Settings
    user: null | User
    window: WindowData
}

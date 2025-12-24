import { reactive } from 'vue'
import shortcutSettings from '@config/Shortcuts'

import type { Store } from '@lctypes/store/Store.ts'

export default reactive<Store>({
    appDataInitialized: false,
    hasUser: true,
    language: null,
    sidebarCollapsed: false,
    settings: {
        shortcuts: shortcutSettings,
    },
    user: null,
    window: {
        width: 0,
        widthWithoutSidebar: 0,
        height: 0,
    },
})

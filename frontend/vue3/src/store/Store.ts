import { reactive } from 'vue'
import shortcutSettings from '@config/Shortcuts'

import type { Store } from '@lctypes/Store.ts'

export default reactive<Store>({
    appDataInitialized: false,
    hasUser: true,
    language: null,
    settings: {
        shortcuts: shortcutSettings,
    },
    user: null,
})

import { reactive } from 'vue'
import type { Store } from '@lctypes/Store.ts'

export default reactive<Store>({
    user: null,
})

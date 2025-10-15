import { reactive } from 'vue'
import type { Store } from './../types/Store'

export default reactive<Store>({
    user: null,
})

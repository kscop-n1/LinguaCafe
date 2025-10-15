interface User {
    name: string
    admin: boolean
}

export interface Store {
    user: null | User
}

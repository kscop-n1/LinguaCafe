export type User = {
    name: string
    email: string
    is_admin: boolean
    password_changed: boolean
}

export type Store = {
    user: null | User
    hasUser: boolean
}

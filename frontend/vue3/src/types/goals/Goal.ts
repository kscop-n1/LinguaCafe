export type Goal = {
    id: number
    name?: string
    user_id?: number
    // todo: refactor to Language type, backend change needed
    language?: string
    type?: string
    // todo: check if these will still be needed, probably should be replaced with polymorphic relationship
    target_id?: null | number
    current_chapter?: null | number
    quantity?: number
    updated_at?: string
    created_at?: string
    todays_quantity?: number
}

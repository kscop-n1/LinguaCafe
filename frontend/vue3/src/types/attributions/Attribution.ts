import type { AttributionLink } from "@lctypes/attributions/AttributionLink"

export type Attribution = {
    name: string
    license: string,
    description?: string,
    links: AttributionLink[]
}

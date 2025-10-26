import type { AttributionLink } from "@lctypes/attributions/AttributionLink"

export type Attribution = {
    name: string
    license: string,
    links: AttributionLink[]
}

export default {
    slots: {
        itemLabel: 'text-default',
        itemLeadingIcon: 'text-default',
    },
    variants: {
        active: {
            false: {
                itemLeadingIcon: 'text-default',
            },
        },
    },
}

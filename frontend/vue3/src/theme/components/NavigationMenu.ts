export default {
    slots: {
        link: 'before:rounded-r-full rounded-r-full select-none cursor-pointer',
        linkLabel: 'select-none py-1',
    },
    // ! symbol used due to compound variants
    variants: {
        active: {
            true: {
                link: 'rounded-r-full select-none bg-primary text-inverted!',
                linkLeadingIcon: 'text-inverted!',
            },

            false: {
                link: 'text-toned',
                linkLeadingIcon: 'text-toned',
            },
        },
    },
}

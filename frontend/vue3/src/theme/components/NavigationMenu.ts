export default {
    slots: {
        link: 'before:rounded-r-full rounded-r-full select-none cursor-pointer',
        linkLabel: 'select-none py-1',
    },
    variants: {
        active: {
            false: {
                link: 'text-toned',
                linkLeadingIcon: 'text-toned',
            },
        },
    },
    compoundVariants: [
        {
            color: 'primary',
            variant: 'pill',
            active: true,
            class: {
                link: 'rounded-r-full select-none bg-primary text-inverted',
                linkLeadingIcon: 'text-inverted',
            },
        },
    ],
}

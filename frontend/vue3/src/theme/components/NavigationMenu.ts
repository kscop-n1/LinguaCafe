export default {
    slots: {
        link: 'before:rounded-r-full rounded-r-full select-none cursor-pointer',
        linkLabel: 'select-none py-1',
        linkTrailingBadge:
            'bg-primary text-inverted group-data-[active]:bg-default group-data-[active]:text-default ring-0 rounded-full',
    },
    variants: {
        active: {
            false: {
                link: 'text-default',
                linkLeadingIcon: 'text-default',
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

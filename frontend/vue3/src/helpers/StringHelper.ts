export function toUpperCase(value: string, firstCharOnly: boolean): string {
    if (firstCharOnly) {
        let firstChar = value[0]?.toUpperCase()
        let restOfChars = value.slice(1)

        return firstChar + restOfChars
    }

    return value.toUpperCase()
}

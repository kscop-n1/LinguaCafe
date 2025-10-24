export enum Tokenizer {
    Stanza = 'stanza',
    Spacy = 'spacy',
}

export type Language = {
    name: string
    databaseDictionaryTableName: string
    linguacafeSupport: boolean
    installRequired: boolean
    wordsSeparatedBySpaces: boolean
    websiteImportSupport: boolean
    tokenizer: Tokenizer
    deeplCode: null | string
    libreTranslateCode: null | string
    myMemoryCode: null | string
    jellyfinCode: null | string
    jellyfinFilenameSlug: null | string
    dictCcCode: null | string
    emoji: string
    dictionaries: string[]
}

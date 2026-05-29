import { defineStore } from 'pinia';

export const useHoverVocabularyBoxStore = defineStore('hoverVocabularyBox', {
    state: () => ({
        active: false as boolean,
        dictionaryTranslation: '' as string,
        apiTranslations: [] as any[],
        dictionarySearchTerm: '' as string,
        disabledWhileSelecting: false as boolean,
        lastHoveredWordIndex: -1 as number,
        key: 0 as number,
        hoveredWords: null as any,
        hoveredPhrase: -1 as number,
        stage: null as number | null,
        reading: '' as string,
        userTranslation: '' as string,
        positionLeft: 0 as number,
        positionTop: 0 as number,
        arrowPosition: 'top' as 'top' | 'bottom',
    }),
    actions: {
        setValue(propertyName: string, value: any) {
            (this as any)[propertyName] = value;
        }
    }
});

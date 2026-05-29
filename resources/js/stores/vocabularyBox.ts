import { defineStore } from 'pinia';

export const useVocabularyBoxStore = defineStore('vocabularyBox', {
    state: () => ({
        active: false as boolean,
        vocabularyBottomSheetVisible: false as boolean,
        key: 0 as number,
        sidebarHidden: true as boolean,

        // data for word
        word: '' as string,
        reading: '' as string,
        baseWord: '' as string,
        baseWordReading: '' as string,
        stage: 0 as number,

        // data for phrase
        phrase: [] as any[],
        phraseReading: '' as string,

        // data for both
        type: 'empty' as string,
        inflections: [] as any[],
        kanjiList: [] as any[],
        translationText: '' as string,

        // ui data
        tab: 0 as number,
        width: 400 as number,
        positionLeft: 0 as number,
        positionTop: 0 as number,
        height: 0 as number,
        searchField: '' as string,
        searchResults: [] as any[],
    }),
    actions: {
        update() {
            this.key++;
        },
        reset() {
            this.active = false;
            this.tab = 0;
            this.searchField = '';
            this.translationText = '';
            this.word = '';
            this.phrase = [];
            this.reading = '';
            this.kanjiList = [];
            this.baseWord = '';
            this.baseWordReading = '';
            this.stage = 2;
            this.type = 'empty';
        },
        setActive(value: boolean) {
            this.active = value;
        },
        setWidth(value: number) {
            this.width = value;
        },
        setHeight(value: number) {
            this.height = value;
        },
        setPositionLeft(value: number) {
            this.positionLeft = value;
        },
        setPositionTop(value: number) {
            this.positionTop = value;
        },
        setType(value: string) {
            this.type = value;
        },
        setWord(value: string) {
            this.word = value;
        },
        setPhrase(value: any[]) {
            this.phrase = value;
        },
        setPhraseReading(value: string) {
            this.phraseReading = value;
        },
        setReading(value: string) {
            this.reading = value;
        },
        setBaseWord(value: string) {
            this.baseWord = value;
        },
        setBaseWordReading(value: string) {
            this.baseWordReading = value;
        },
        setTranslationText(value: string) {
            this.translationText = value;
        },
        setStage(value: number) {
            this.stage = value;
        },
        setSearchField(value: string) {
            this.searchField = value;
        },
        setInflections(value: any[]) {
            this.inflections = value;
        },
        setVocabularyBottomSheetVisible(value: boolean) {
            this.vocabularyBottomSheetVisible = value;
        },
        setSidebarHidden(value: boolean) {
            this.sidebarHidden = value;
        },
        pushWordToPhrase(value: any) {
            this.phrase.push(value);
        },
        pushKanjiToList(value: any) {
            this.kanjiList.push(value);
        },
        appendSearchField(value: string) {
            this.searchField += value;
        },
        appendReading(value: string) {
            this.reading += value;
        },
    }
});

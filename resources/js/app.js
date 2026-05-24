import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createStore } from 'vuex';
import vuetify from './vuetify';

import './bootstrap';
import '../sass/app.scss';

window.__LINGUACAFE_BOOTSTRAP_STARTED = true;
const rootElement = document.querySelector('#app');
const app = createApp({
    template: rootElement ? rootElement.innerHTML : '',
});

app.mixin({
    computed: {
        currentThemeColors() {
            return this.$vuetify?.theme?.current?.value?.colors || this.$vuetify?.theme?.global?.current?.value?.colors || {};
        },
        dialogValue() {
            return this.$attrs?.modelValue !== undefined ? this.$attrs.modelValue : this.value;
        },
    },
    methods: {
        updateValue(value) {
            this.$emit('input', value);
            this.$emit('update:modelValue', value);
        },
    },
});


app.config.globalProperties.$cookie = {
    get(name) {
        const cookies = document.cookie ? document.cookie.split('; ') : [];
        const prefix = name + '=';
        const cookie = cookies.find((entry) => entry.indexOf(prefix) === 0);
        return cookie ? decodeURIComponent(cookie.slice(prefix.length)) : null;
    },
    set(name, value, days = 365) {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/';
    },
    delete(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    },
};


// layout
import Layout from './components/Layout.vue';
app.component('layout', Layout);

// library
import NoBookCoverIcon from './components/Icons/NoBookCoverIcon.vue';
import Book from './components/Library/Book.vue';
import BookListTable from './components/Library/BookListLayout/BookListTable.vue';
import BookListDetailed from './components/Library/BookListLayout/BookListDetailed.vue';
import BookListCoverOnly from './components/Library/BookListLayout/BookListCoverOnly.vue';
import EditBookDialog from './components/Library/EditBookDialog.vue';
import BookChapters from './components/Library/BookChapters.vue';
import EditBookChapterDialog from './components/Library/EditBookChapterDialog.vue';
import DeleteBookChapterDialog from './components/Library/DeleteBookChapterDialog.vue';
import DeleteBookDialog from './components/Library/DeleteBookDialog.vue';
app.component('NoBookCoverIcon', NoBookCoverIcon);
app.component('book', Book);
app.component('book-list-table', BookListTable);
app.component('book-list-detailed', BookListDetailed);
app.component('book-list-cover-only', BookListCoverOnly);
app.component('edit-book-dialog', EditBookDialog);
app.component('book-chapters', BookChapters);
app.component('edit-book-chapter-dialog', EditBookChapterDialog);
app.component('delete-book-chapter-dialog', DeleteBookChapterDialog);
app.component('delete-book-dialog', DeleteBookDialog);

// library import
import ImportDialog from './components/Library/Import/ImportDialog.vue';
import ImportTypeSelection from './components/Library/Import/ImportTypeSelection.vue';
import ImportPlainTextSource from './components/Library/Import/ImportSource/ImportPlainTextSource.vue';
import ImportTextFileSource from './components/Library/Import/ImportSource/ImportTextFileSource.vue';
import ImportSubtitleFileSource from './components/Library/Import/ImportSource/ImportSubtitleFileSource.vue';
import ImportEbookFileSource from './components/Library/Import/ImportSource/ImportEbookFileSource.vue';
import ImportYoutubeSubtitleSource from './components/Library/Import/ImportSource/ImportYoutubeSubtitleSource.vue';
import ImportJellyfinSubtitleSource from './components/Library/Import/ImportSource/ImportJellyfinSubtitleSource.vue';
import ImportWebsiteSource from './components/Library/Import/ImportSource/ImportWebsiteSource.vue';
import ImportLibraryOptions from './components/Library/Import/ImportLibraryOptions.vue';
import ImportOptions from './components/Library/Import/ImportOptions.vue';
app.component('import-dialog', ImportDialog);
app.component('import-type-selection', ImportTypeSelection);
app.component('import-plain-text-source', ImportPlainTextSource);
app.component('import-text-file-source', ImportTextFileSource);
app.component('import-subtitle-file-source', ImportSubtitleFileSource);
app.component('import-ebook-file-source', ImportEbookFileSource);
app.component('import-youtube-subtitle-source', ImportYoutubeSubtitleSource);
app.component('import-jellyfin-subtitle-source', ImportJellyfinSubtitleSource);
app.component('import-website-source', ImportWebsiteSource);
app.component('import-library-options', ImportLibraryOptions);
app.component('import-options', ImportOptions);

// home page
import Calendar from './components/Home/Calendar.vue';
import Goals from './components/Home/Goals.vue';
import Goal from './components/Home/Goal.vue';
import EditGoalDialog from './components/Home/EditGoalDialog.vue';
import Statistics from './components/Home/Statistics.vue';
app.component('calendar', Calendar);
app.component('goals', Goals);
app.component('goal', Goal);
app.component('edit-goal-dialog', EditGoalDialog);
app.component('statistics', Statistics);

// text
import TextBlockGroup from './components/Text/TextBlockGroup.vue';
import VocabularyBox from './components/Text/VocabularyBox.vue';
import VocabularyBottomSheet from './components/Text/VocabularyBottomSheet.vue';
import VocabularyHoverBox from './components/Text/VocabularyHoverBox.vue';
import VocabularySideBox from './components/Text/VocabularySideBox.vue';
import VocabularySearchBox from './components/Text/VocabularySearchBox.vue';
import DeletePhraseDialog from './components/Text/DeletePhraseDialog.vue';
app.component('text-block-group', TextBlockGroup);
app.component('vocabulary-box', VocabularyBox);
app.component('vocabulary-bottom-sheet', VocabularyBottomSheet);
app.component('vocabulary-hover-box', VocabularyHoverBox);
app.component('vocabulary-side-box', VocabularySideBox);
app.component('vocabulary-search-box', VocabularySearchBox);
app.component('delete-phrase-dialog', DeletePhraseDialog);

// text reader
import TextReaderSettings from './components/TextReader/TextReaderSettings.vue';
import TextReaderGlossary from './components/TextReader/TextReaderGlossary.vue';
import TextReaderChapterList from './components/TextReader/TextReaderChapterList.vue';
import TextReaderHotkeyInformationDialog from './components/TextReader/TextReaderHotkeyInformationDialog.vue';
app.component('text-reader-hotkey-information-dialog', TextReaderHotkeyInformationDialog);
app.component('text-reader-settings', TextReaderSettings);
app.component('text-reader-glossary', TextReaderGlossary);
app.component('text-reader-chapter-list', TextReaderChapterList);

// Jellyfin
import JellyfinSubtitleList from './components/Library/Import/ImportSource/JellyfinSubtitleList.vue';
app.component('jellyfin-subtitle-list', JellyfinSubtitleList);

// vocabulary
import VocabularyEditDialog from './components/Vocabulary/VocabularyEditDialog.vue';
import VocabularyExportDialog from './components/Vocabulary/VocabularyExportDialog.vue';
import VocabularyImportDialog from './components/Vocabulary/VocabularyImportDialog.vue';
app.component('vocabulary-edit-dialog', VocabularyEditDialog);
app.component('vocabulary-export-dialog', VocabularyExportDialog);
app.component('vocabulary-import-dialog', VocabularyImportDialog);

// review
import ReviewHotkeyInformationDialog from './components/Review/ReviewHotkeyInformationDialog.vue';
import ReviewSettings from './components/Review/ReviewSettings.vue';
app.component('review-hotkey-information-dialog', ReviewHotkeyInformationDialog);
app.component('review-settings', ReviewSettings);

// dialogs
import LogoutDialog from './components/Dialogs/LogoutDialog.vue';
import ErrorDialog from './components/Dialogs/ErrorDialog.vue';
import StartReviewDialog from './components/Dialogs/StartReviewDialog.vue';
import ThemeSelectionDialog from './components/Dialogs/ThemeSelectionDialog.vue';
import LanguageSelectionDialog from './components/Dialogs/LanguageSelectionDialog.vue';
app.component('logout-dialog', LogoutDialog);
app.component('error-dialog', ErrorDialog);
app.component('start-review-dialog', StartReviewDialog);
app.component('theme-selection-dialog', ThemeSelectionDialog);
app.component('language-selection-dialog', LanguageSelectionDialog);

// user settings
import UserSettingsAccount from './components/UserSettings/UserSettingsAccount.vue';
import ChangePasswordDialog from './components/UserSettings/ChangePasswordDialog.vue';
import UserSettingsThemes from './components/UserSettings/ThemeSettings/UserSettingsThemes.vue';
import UserSettingsTextStyling from './components/UserSettings/ThemeSettings/UserSettingsTextStyling.vue';
import UserSettingsTextStylingSample from './components/UserSettings/ThemeSettings/UserSettingsTextStylingSample.vue';
import ResetTextStylingDialog from './components/UserSettings/ThemeSettings/ResetTextStylingDialog.vue';
app.component('user-settings-themes', UserSettingsThemes);
app.component('change-password-dialog', ChangePasswordDialog);
app.component('user-settings-account', UserSettingsAccount);
app.component('user-settings-text-styling', UserSettingsTextStyling);
app.component('user-settings-text-styling-sample', UserSettingsTextStylingSample);
app.component('reset-text-styling-dialog', ResetTextStylingDialog);

// admin settings
import AdminDashboard from './components/Admin/AdminDashboard.vue';
import AdminUserSettings from './components/Admin/AdminUserSettings.vue';
import AdminFontTypeSettings from './components/Admin/AdminFontTypeSettings.vue';
import AdminEditFontTypeDialog from './components/Admin/AdminEditFontTypeDialog.vue';
import AdminDeleteFontTypeDialog from './components/Admin/AdminDeleteFontTypeDialog.vue';
import AdminLanguageSettings from './components/Admin/AdminLanguageSettings.vue';
import AdminInstallLanguageDialog from './components/Admin/AdminInstallLanguageDialog.vue';
import AdminUninstallLanguagesDialog from './components/Admin/AdminUninstallLanguagesDialog.vue';
import AdminDictionarySettings from './components/Admin/AdminDictionarySettings.vue';
import AdminDeleteDictionaryDialog from './components/Admin/AdminDeleteDictionaryDialog.vue';
import AdminEditDictionaryDialog from './components/Admin/AdminEditDictionaryDialog.vue';
import AdminApiSettings from './components/Admin/AdminApiSettings.vue';
import AdminDictionaryImportDialog from './components/Admin/AdminDictionaryImportDialog.vue';
import AdminExternalDictionaryImport from './components/Admin/AdminExternalDictionaryImport.vue';
import AdminSupportedDictionaryImport from './components/Admin/AdminSupportedDictionaryImport.vue';
import AdminDeeplDictionaryCreation from './components/Admin/AdminDeeplDictionaryCreation.vue';
import AdminMyMemoryDictionaryCreation from './components/Admin/AdminMyMemoryDictionaryCreation.vue';
import AdminLibreTranslateDictionaryCreation from './components/Admin/AdminLibreTranslateDictionaryCreation.vue';
import AdminCustomApiDictionaryCreation from './components/Admin/AdminCustomApiDictionaryCreation.vue';
import AdminEditUserDialog from './components/Admin/AdminEditUserDialog.vue';
import AdminReviewSettings from './components/Admin/AdminReviewSettings.vue';
app.component('admin-dashboard', AdminDashboard);
app.component('admin-user-settings', AdminUserSettings);
app.component('admin-language-settings', AdminLanguageSettings);
app.component('admin-font-type-settings', AdminFontTypeSettings);
app.component('admin-edit-font-type-dialog', AdminEditFontTypeDialog);
app.component('admin-delete-font-type-dialog', AdminDeleteFontTypeDialog);
app.component('admin-install-language-dialog', AdminInstallLanguageDialog);
app.component('admin-uninstall-languages-dialog', AdminUninstallLanguagesDialog);
app.component('admin-dictionary-settings', AdminDictionarySettings);
app.component('admin-delete-dictionary-dialog', AdminDeleteDictionaryDialog);
app.component('admin-edit-dictionary-dialog', AdminEditDictionaryDialog);
app.component('admin-api-settings', AdminApiSettings);
app.component('admin-dictionary-import-dialog', AdminDictionaryImportDialog);
app.component('admin-external-dictionary-import', AdminExternalDictionaryImport);
app.component('admin-supported-dictionary-import', AdminSupportedDictionaryImport);
app.component('admin-deepl-dictionary-creation', AdminDeeplDictionaryCreation);
app.component('admin-my-memory-dictionary-creation', AdminMyMemoryDictionaryCreation);
app.component('admin-custom-api-dictionary-creation', AdminCustomApiDictionaryCreation);
app.component('admin-libre-translate-dictionary-creation', AdminLibreTranslateDictionaryCreation);
app.component('admin-edit-user-dialog', AdminEditUserDialog);
app.component('admin-review-settings', AdminReviewSettings);


// user manual
import UserManual from './components/UserManual/UserManual.vue';
import UserManualIntroduction from './components/UserManual/Pages/UserManualIntroduction.vue';
import UserManualBackup from './components/UserManual/Pages/UserManualBackup.vue';
import UserManualLanguages from './components/UserManual/Pages/UserManualLanguages.vue';
import UserManualReading from './components/UserManual/Pages/UserManualReading.vue';
import UserManualVocabularyImport from './components/UserManual/Pages/UserManualVocabularyImport.vue';

app.component('user-manual-introduction', UserManualIntroduction);
app.component('user-manual-backup', UserManualBackup);
app.component('user-manual-languages', UserManualLanguages);
app.component('user-manual-reading', UserManualReading);
app.component('user-manual-vocabulary-import', UserManualVocabularyImport);

import DevelopmentTools from './components/DevelopmentTools.vue';
import LoginForm from './components/Login/LoginForm.vue';
import UserSettingsLayout from './components/UserSettings/UserSettingsLayout.vue';
import AdminSettingsLayout from './components/Admin/AdminSettingsLayout.vue';
import Home from './components/Home/Home.vue';
import PatchNotes from './components/Home/PatchNotes.vue';
import Attributions from './components/Home/Attributions.vue';
import Library from './components/Library/Library.vue';
import TextReader from './components/TextReader/TextReader.vue';
import Review from './components/Review/Review.vue';
import Vocabulary from './components/Vocabulary/Vocabulary.vue';
import KanjiList from './components/Kanji/KanjiList.vue';
import KanjiDetails from './components/Kanji/KanjiDetails.vue';
app.component('attributions', Attributions);

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/dev', component: DevelopmentTools },
        { path: '/', component: Home },
        { path: '/user-settings', component: UserSettingsLayout },
        { path: '/admin/:page?', component: AdminSettingsLayout },
        { path: '/user-manual/:currentPage?', component: UserManual },
        { path: '/patch-notes', component: PatchNotes },
        { path: '/attributions', component: Attributions },
        { path: '/login', component: LoginForm },
        { path: '/books/:bookId?', component: Library },
        { path: '/chapters/read/:chapterId', component: TextReader },
        { path: '/review/:practiceMode?/:bookId?/:chapterId?', component: Review },
        { path: '/vocabulary/search', component: Vocabulary },
        { path: '/vocabulary/search/:text/:stage/:book/:chapter/:translation/:phrases/:orderBy/:page', component: Vocabulary },
        { path: '/kanji/search', component: KanjiList },
        { path: '/kanji/:character', component: KanjiDetails },
    ]
})

// vuex
import SharedStore from './vuex/Shared.js';
import InteractiveTextStore from './vuex/InteractiveText.js';
import HoverVocabularyBoxStore from './vuex/HoverVocabularyBox.js';
import VocabularyBoxStore from './vuex/VocabularyBox.js';

const store = createStore({
    modules: {
        shared: SharedStore,
        interactiveText: InteractiveTextStore,
        hoverVocabularyBox: HoverVocabularyBoxStore,
        vocabularyBox: VocabularyBoxStore,
    }
});

try {
    app.use(store);
    app.use(router);
    app.use(vuetify);
    app.config.globalProperties.$vuetify = app.config.globalProperties.$vuetify || vuetify;
    app.mount('#app');
    window.__LINGUACAFE_BOOTSTRAP_MOUNTED = true;
} catch (error) {
    window.__LINGUACAFE_BOOTSTRAP_ERROR = error && (error.stack || error.message || String(error));
    throw error;
}



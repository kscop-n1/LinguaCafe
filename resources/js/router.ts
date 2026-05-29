import { createRouter, createWebHistory } from 'vue-router';

// Lazy load components for better bundle size and performance
const DevelopmentTools = () => import('./components/DevelopmentTools.vue');
const Home = () => import('./components/Home/Home.vue');
const UserSettingsLayout = () => import('./components/UserSettings/UserSettingsLayout.vue');
const AdminSettingsLayout = () => import('./components/Admin/AdminSettingsLayout.vue');
const UserManual = () => import('./components/UserManual/UserManual.vue');
const PatchNotes = () => import('./components/Home/PatchNotes.vue');
const Attributions = () => import('./components/Home/Attributions.vue');
const LoginForm = () => import('./components/Login/LoginForm.vue');
const Library = () => import('./components/Library/Library.vue');
const TextReader = () => import('./components/TextReader/TextReader.vue');
const Review = () => import('./components/Review/Review.vue');
const Vocabulary = () => import('./components/Vocabulary/Vocabulary.vue');
const KanjiList = () => import('./components/Kanji/KanjiList.vue');
const KanjiDetails = () => import('./components/Kanji/KanjiDetails.vue');

const routes = [
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
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

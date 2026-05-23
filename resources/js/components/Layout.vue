<template>
   <v-app :class="{'eink': theme == 'eink', 'dark': theme == 'dark'}">

        <!-- Dialogs -->
        <start-review-dialog v-model="startReviewDialog" />
        <logout-dialog v-model="logoutDialog"/>

        <template v-if="$route.path !== '/login'">
            <theme-selection-dialog v-model="themeSelectionDialog" @input="updateTheme"></theme-selection-dialog>
            <language-selection-dialog v-model="languageSelectionDialog"></language-selection-dialog>
            <v-navigation-drawer
                id="navigation-drawer"
                app
                dense
                :class="{'eink': theme == 'eink'}"
                :rail="display.md || navbarCollapsed"
                :permanent="display.mdAndUp"
                :model-value="isDrawerOpen"
                color="foreground"
            >
                <!-- Logo -->
                <div id="logo" class="d-flex justify-center my-5" v-if="display.lgAndUp && !navbarCollapsed">
                    <img src="/icon512rounded.png" class="mr-2" width="32px" height="32px"/>
                    <span class="text--text">Lingua Cafe</span>
                </div>

                <v-list nav shaped dense class="pl-0">
                    <!-- Navigation buttons -->
                    <v-list-item
                        class="navigation-button"
                        v-for="(item, index) in navigation"
                        :key="index"
                        :to="item.url"
                        @click="navigationClick(item.name, $event)"
                    >
                        <v-icon> {{ item.icon }} </v-icon>
                        <span class="pl-6"> {{ item.name }} </span>
                    </v-list-item>
                    <v-list-item class="navigation-button" @click="openLogoutDialog">
                        <v-icon> mdi-logout </v-icon>
                        <span class="pl-6"> Logout </span>
                    </v-list-item>
                </v-list>

                <template v-slot:append>
                    <!-- Large navigation drawer -->
                    <template v-if="!display.md && !navbarCollapsed">
                        <v-list nav shaped dense class="pl-0">
                            <!-- Navigation buttons -->
                            <v-list-item class="navigation-button" @click="collapseNavbar">
                                <v-icon> mdi-arrow-collapse-left </v-icon>
                                <span class="pl-6"> Hide</span>
                            </v-list-item>
                            <v-list-item class="navigation-button" @click="themeSelectionDialog = true;">
                                <v-icon> mdi-palette </v-icon>
                                <span class="pl-6"> Theme</span>
                            </v-list-item>
                            <v-list-item class="navigation-button" @click="languageSelectionDialog = true;">
                                <v-img class="border" :src="'/images/flags/' + selectedLanguage.toLowerCase() + '.png'" max-width="26" height="17"></v-img>
                                <span class="pl-5"> Language</span>
                            </v-list-item>
                        </v-list>
                    </template>

                    <!-- Mini navigation drawer -->
                    <template v-else>
                        <v-btn v-if="display.lgAndUp" id="collapse" rounded text class="mini-drawer-button" @click="expandNavbar" title="Expand sidebar">
                            <v-icon>mdi-arrow-collapse-right</v-icon>
                        </v-btn>
                        <v-btn id="theme" rounded text class="mini-drawer-button" @click="themeSelectionDialog = true" title="Theme">
                            <v-icon>mdi-palette</v-icon>
                        </v-btn>
                        <v-btn id="language" rounded text class="mini-drawer-button" @click="languageSelectionDialog = true" title="Select language">
                            <v-img :src="'/images/flags/' + selectedLanguage.toLowerCase() + '.png'" max-width="31" height="20"></v-img>
                        </v-btn>
                    </template>
                </template>
            </v-navigation-drawer>

            <!-- Bottom navigation -->
            <v-bottom-navigation dense grow shift class="d-flex d-sm-flex d-md-none" dark background-color="primary">
                <v-btn class="text-decoration-none" width="60" style="float: left;" @click="drawer = true;">
                    <span>More</span>
                    <v-icon>mdi-menu</v-icon>
                </v-btn><v-spacer></v-spacer>
                <template v-for="(item, index) in navigation" :key="index">
                    <v-btn
                        v-if="item.bottomNav"
                        class="text-decoration-none"
                        grow
                        :to="item.url"
                    >
                        <span>{{ item.name }}</span>
                        <v-icon>{{ item.icon }}</v-icon>
                    </v-btn>
                </template>
            </v-bottom-navigation>
        </template>
        <v-main :style="{background: currentThemeBackground, ...textStyling}" :class="{ eink: theme == 'eink'}">
            <router-view :user-count="$props._userCount" :language="selectedLanguage" :key="$route.fullPath"></router-view>
        </v-main>
    </v-app>
</template>

<script>
    import ThemeService from './../services/ThemeService';
    import TextStylingService from './../services/TextStylingService';
    import FontTypeService from './../services/FontTypeService';
    import { DefaultLocalStorageManager } from './../services/LocalStorageManagerService';
    
    export default {
        data: function() {
            return {
                selectedLanguage: this.$props._selectedLanguage,
                theme: DefaultLocalStorageManager.loadSetting("theme") || 'light',
                logoutDialog: false,
                themeSelectionDialog: false,
                languageSelectionDialog: false,
                startReviewDialog: false,
                drawer: window.innerWidth >= 960,
                navbarVisible: true,
                navbarCollapsed: false,
                windowWidth: window.innerWidth,
                navigation: [
                    {
                        name: 'Home',
                        url: '/',
                        icon: 'mdi-home',
                        bottomNav: true,
                    },
                    {
                        name: 'Library',
                        url: '/books',
                        icon: 'mdi-bookshelf',
                        bottomNav: true,
                    },
                    {
                        name: 'Vocabulary',
                        url: '/vocabulary/search',
                        icon: 'mdi-translate',
                        bottomNav: true,
                    },
                    {
                        name: 'Review',
                        url: '',
                        click: this.openStartReviewDialog,
                        icon: 'mdi-playlist-check',
                        bottomNav: false,
                    },
                    {
                        name: 'User settings',
                        url: '/user-settings',
                        icon: 'mdi-account-cog',
                        bottomNav: false,
                    },
                    {
                        name: 'User manual',
                        url: '/user-manual',
                        icon: 'mdi-account-question',
                        bottomNav: false,
                    }
                ],
            }
        },
        computed: {
            display() {
                return {
                    md: this.windowWidth >= 960 && this.windowWidth < 1280,
                    mdAndUp: this.windowWidth >= 960,
                    lgAndUp: this.windowWidth >= 1280,
                };
            },
            currentThemeBackground() {
                const themes = this.$store.state.shared.vuetifyThemeSettings || this.$props.themeSettings?.vuetifyThemes;

                if (this.theme === 'dark') {
                    return themes?.dark?.background || '#28272C';
                }

                if (this.theme === 'eink') {
                    return themes?.eink?.background || '#F2F3F5';
                }

                return themes?.light?.background || '#F2F3F5';
            },
            isDrawerOpen() {
                return this.display.mdAndUp || this.drawer;
            },
            textStyling: function() {
                let settingsObject = this.$store.state.shared.textStylingSettings

                if (settingsObject === null) {
                    settingsObject = TextStylingService.getDefaultTextStylingSettings()
                }

                const settingsCssObject = TextStylingService.getTextStylingSettingsObject(settingsObject)
                return settingsCssObject[this.theme]
            }
        },
        props: {
            _selectedLanguage: String,
            _userName: {
                type: String,
                default: '',
            },
            _userEmail: {
                type: String,
                default: '',
            },
            _userUuid: {
                type: String,
                default: '',
            },
            _userCount: Number,
            _isAdmin: Boolean,
            themeSettings: {
                type: Object,
                default: null,
            }
        },
        beforeMount() {
            // set store data
            this.$store.commit('shared/setUuid', this.$props._userUuid);
            this.$store.commit('shared/setUserName', this.$props._userName);
            this.$store.commit('shared/setUserEmail', this.$props._userEmail);
            this.$store.commit('shared/setUserAdmin', this.$props._isAdmin);

            if (this.$props._selectedLanguage == 'japanese') {
                this.navigation.splice(3, 0, {
                    name: 'Kanji',
                    url: '/kanji/search',
                    icon: 'mdi-ideogram-cjk',
                    bottomNav: false,
                });
            }

            if(this.$store.getters['shared/userAdmin']) {
                this.navigation.push({
                    name: 'Admin settings',
                    url: '/admin',
                    icon: 'mdi-shield-lock',
                    bottomNav: false,
                });
            }

            this.initializeThemes();

            // Watch OS theme change. Currently disabled to 
            // const preferredDarkTheme = window.matchMedia("(prefers-color-scheme: dark)");
            // preferredDarkTheme.addEventListener("change", this.loadSelectedTheme);

            // load navbar status
            const savedNavbarCollapsed = DefaultLocalStorageManager.loadSetting('navbar-collapsed');
            this.navbarCollapsed = savedNavbarCollapsed ? savedNavbarCollapsed === 'true' : false;
        },
        mounted() {
            window.addEventListener('resize', this.updateWindowWidth);

            // load default and selected font types into the dom
            var fontTypeService = new FontTypeService(this.selectedLanguage, () => {
                fontTypeService.loadSelectedFontTypeIntoDom();
                fontTypeService.loadDefaultFontTypeIntoDom();
            });
        },
        beforeUnmount() {
            window.removeEventListener('resize', this.updateWindowWidth);
        },
        methods: {
            updateWindowWidth() {
                this.windowWidth = window.innerWidth;
                this.updateDrawerForViewport();
            },
            updateDrawerForViewport() {
                this.drawer = this.windowWidth >= 960;
            },
            initializeThemes() {
                this.loadSelectedTheme();
                ThemeService.setDefaultVuetifyTheme(this.$vuetify);

                if (this.$props.themeSettings?.vuetifyThemes) {
                    this.$store.commit('shared/setVuetifyThemeSettings', this.$props.themeSettings.vuetifyThemes)
                    this.$store.commit('shared/setTextStylingSettings', this.$props.themeSettings.textStyling)
                }

                ThemeService.setVuetifyTheme(this.$vuetify, this.$store)
            },
            loadSelectedTheme() {
                const autoEnabled = ThemeService.isAuto();
                const preferredDarkTheme = window.matchMedia("(prefers-color-scheme: dark)");

                if (autoEnabled) {
                    // auto-select user's system theme if 'auto' is enabled
                    if (preferredDarkTheme.matches) {
                        this.theme = 'dark';
                        console.log('auto dark')
                    } else {
                        this.theme = 'light';
                        console.log('auto light')
                    }

                    DefaultLocalStorageManager.saveSetting('theme', this.theme);
                } else {
                    // otherwise use saved theme
                    const savedTheme = DefaultLocalStorageManager.loadSetting('theme');
                    this.theme = savedTheme ? savedTheme : 'light';
                }
            },
            collapseNavbar() {
                this.navbarCollapsed = true;
                DefaultLocalStorageManager.saveSetting('navbar-collapsed', this.navbarCollapsed);
            },
            expandNavbar() {
                this.navbarCollapsed = false;
                DefaultLocalStorageManager.saveSetting('navbar-collapsed', this.navbarCollapsed);
            },
            navigationClick(itemName, event) {
                if (itemName === 'Review') {
                    this.startReviewDialog = true;
                    event.preventDefault();
                }

                // clicked on user manual
                if (itemName === 'User manual' && this.$route.path !== '/user-manual') {
                    this.$router.push({ path: '/user-manual', replace: true });
                }

            },
            updateTheme() {
                const savedTheme = DefaultLocalStorageManager.loadSetting('theme');
                this.theme = savedTheme ? savedTheme : 'light';
            },
            openLogoutDialog() {
                this.logoutDialog = true;
            },
        }
    }
</script>

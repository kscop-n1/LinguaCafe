import { DefaultLocalStorageManager } from './LocalStorageManagerService'
import defaultThemes from './../themes';

const localStorageManager = DefaultLocalStorageManager

class ThemeService {
    constructor() {
        
    }

    getCookie(name) {
        const prefix = `${name}=`;
        const cookies = document.cookie ? document.cookie.split('; ') : [];
        const cookie = cookies.find((entry) => entry.indexOf(prefix) === 0);
        return cookie ? decodeURIComponent(cookie.slice(prefix.length)) : null;
    }

    getStoredTheme() {
        return this.getCookie('theme') || localStorageManager.loadSetting('theme');
    }

    getResolvedTheme() {
        if (localStorageManager.loadSetting('theme-auto')) {
            return this.getAutoTheme();
        }

        return this.getStoredTheme() || 'light';
    }

    setDefaultVuetifyTheme(vuetifyHandler) {
        const themeName = this.getResolvedTheme();
        const lightTheme = themeName === 'eink' ? defaultThemes.eink : defaultThemes.light;

        this.setThemeColors(vuetifyHandler, 'light', lightTheme);
        this.setThemeColors(vuetifyHandler, 'dark', defaultThemes.dark);
        this.setThemeColors(vuetifyHandler, 'eink', defaultThemes.eink);
        this.setActiveTheme(vuetifyHandler, themeName === 'dark' ? 'dark' : themeName === 'eink' ? 'eink' : 'light');
    }

    setThemeColors(vuetifyHandler, themeName, colors) {
        if (vuetifyHandler?.theme?.themes?.value) {
            vuetifyHandler.theme.themes.value[themeName] = {
                ...(vuetifyHandler.theme.themes.value[themeName] || {}),
                dark: themeName === 'dark',
                colors: JSON.parse(JSON.stringify(colors)),
                variables: {},
            };
            return;
        }

        if (vuetifyHandler?.theme?.themes) {
            vuetifyHandler.theme.themes[themeName] = {
                ...(vuetifyHandler.theme.themes[themeName] || {}),
                dark: themeName === 'dark',
                colors: JSON.parse(JSON.stringify(colors)),
                variables: {},
            };
        }
    }

    setActiveTheme(vuetifyHandler, themeName) {
        if (vuetifyHandler?.theme?.global?.name?.value !== undefined) {
            vuetifyHandler.theme.global.name.value = themeName;
            return;
        }

        if (vuetifyHandler?.theme?.global?.name !== undefined) {
            vuetifyHandler.theme.global.name = themeName;
            return;
        }

        if (vuetifyHandler?.theme) {
            vuetifyHandler.theme.dark = themeName === 'dark';
        }
    }


    // applies the vuetify theme stored in the vuex store
    setVuetifyTheme(vuetifyHandler, storeHandler) {
        const vuetifyThemeSettings = storeHandler.state.shared.vuetifyThemeSettings

        if (vuetifyThemeSettings === null) {
            return
        }

        let lightTheme = {}
        let darkTheme = {}
        const currentTheme = this.getResolvedTheme();
        let themeSettingNames = Object.keys(defaultThemes.light)
        themeSettingNames.forEach((name) => {
            if (currentTheme === 'eink') {
                lightTheme[name] = JSON.parse(JSON.stringify(defaultThemes['eink'][name]));
            } else {
                lightTheme[name] = vuetifyThemeSettings['light'][name] ?? JSON.parse(JSON.stringify(defaultThemes['light'][name]));
            }

            darkTheme[name] = vuetifyThemeSettings['dark'][name] ?? JSON.parse(JSON.stringify(defaultThemes['dark'][name]));
        });

        this.setThemeColors(vuetifyHandler, 'light', lightTheme);
        this.setThemeColors(vuetifyHandler, 'dark', darkTheme);
        this.setThemeColors(vuetifyHandler, 'eink', currentTheme === 'eink' ? lightTheme : defaultThemes.eink);
    }

    getCurrentTheme() {
        if (localStorageManager.loadSetting('theme-auto')) {
            return 'auto'
        } else {
            return this.getStoredTheme() || 'light';
        };
    }

    getAutoTheme() {
        let autoTheme;
        if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
            autoTheme = 'dark';
        } else {
            autoTheme = 'light';
        }

        return autoTheme;
    }

    isAuto() {
        return localStorageManager.loadSetting('theme-auto') === true;
    }
}

export default new ThemeService();

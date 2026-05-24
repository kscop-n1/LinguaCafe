import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import { aliases, mdi } from 'vuetify/iconsets/mdi';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import defaultThemes from './themes';

export default createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: { mdi },
    },
    theme: {
        defaultTheme: 'light',
        themes: {
            light: { dark: false, colors: defaultThemes.light, variables: {} },
            dark: { dark: true, colors: defaultThemes.dark, variables: {} },
            eink: { dark: false, colors: defaultThemes.eink, variables: {} },
        },
    },
});

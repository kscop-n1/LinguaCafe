import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = window.Pusher || Pusher;

function createEcho() {
    return new Echo({
        broadcaster: 'pusher',
        key: window.__LINGUACAFE_WEBSOCKET_APP_KEY,
        cluster: 'mt1',
        forceTLS: false,
        wsHost: window.location.hostname,
        wsPort: 6001,
        enabledTransports: ['ws', 'wss'],
    });
}

export default {
    namespaced: true,
    state: () => ({
        userUuid: '',
        userName: false,
        userEmail: false,
        userAdmin: false,
        vuetifyThemeSettings: null,
        textStylingSettings: null,
        echo: null
    }),
    mutations: {
        setUuid (state, userUuid) {
            state.userUuid = userUuid;
        },
        setUserName (state, userName) {
            state.userName = userName;
        },
        setUserEmail (state, userEmail) {
            state.userEmail = userEmail;
        },
        setUserAdmin (state, userAdmin) {
            state.userAdmin = userAdmin;
        },
        setVuetifyThemeSettings (state, vuetifyThemeSettings) {
            state.vuetifyThemeSettings = vuetifyThemeSettings;
        },
        setTextStylingSettings (state, textStylingSettings) {
            state.textStylingSettings = textStylingSettings;
        }
    },
    getters: {
        echo (state) {
            if (!state.echo) {
                state.echo = createEcho();
            }
            return state.echo;
        },
        userUuid(state) {
            return state.userUuid;
        },
        userAdmin(state) {
            return state.userAdmin;
        }
    }
}

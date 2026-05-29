import { defineStore } from 'pinia';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Ensure Pusher is accessible globally for Echo
(window as any).Pusher = Pusher;

export const useSharedStore = defineStore('shared', {
    state: () => ({
        userUuid: '' as string,
        userName: false as string | boolean,
        userEmail: false as string | boolean,
        userAdmin: false as boolean,
        vuetifyThemeSettings: null as any,
        textStylingSettings: null as any,
        echo: new Echo({
            broadcaster: 'pusher',
            key: 'wjp2pou6ebgibtwccqsj',
            cluster: 'mt1',
            forceTLS: false,
            wsHost: window.location.hostname,
            wsPort: 6001,
            enabledTransports: ['ws', 'wss'],
        }) as Echo
    }),
    actions: {
        setUuid(userUuid: string) {
            this.userUuid = userUuid;
        },
        setUserName(userName: string | boolean) {
            this.userName = userName;
        },
        setUserEmail(userEmail: string | boolean) {
            this.userEmail = userEmail;
        },
        setUserAdmin(userAdmin: boolean) {
            this.userAdmin = userAdmin;
        },
        setVuetifyThemeSettings(vuetifyThemeSettings: any) {
            this.vuetifyThemeSettings = vuetifyThemeSettings;
        },
        setTextStylingSettings(textStylingSettings: any) {
            this.textStylingSettings = textStylingSettings;
        }
    },
    getters: {
        getEcho(): Echo {
            return this.echo;
        },
        getUserUuid(): string {
            return this.userUuid;
        },
        getUserAdmin(): boolean {
            return this.userAdmin;
        }
    }
});

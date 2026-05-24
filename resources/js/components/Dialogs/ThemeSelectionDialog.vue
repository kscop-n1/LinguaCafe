<template>
    <v-dialog :model-value="dialogValue" @update:model-value="updateValue" persistent max-width="300px">
        <v-card class="rounded-lg">
            <v-card-title>
                <span class="text-h5">Theme</span>
                <v-spacer></v-spacer>
                <v-btn icon @click="close">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>
            <v-card-text>
                <v-list nav rounded>
                    <v-list-item
                        v-for="(theme, themeName) in displayNames"
                        :key="themeName"
                        class="my-1"
                        :active="selectedTheme === themeName"
                        active-color="primary"
                        rounded
                        @click="selectTheme(themeName)"
                    >
                        <template #prepend>
                            <v-avatar rounded="0" size="60" class="theme-selection-dialog__icon">
                                <v-icon>{{ theme.icon }}</v-icon>
                            </v-avatar>
                        </template>
                        <v-list-item-title>{{ theme.name }}</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn rounded text @click="close">Cancel</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
    import ThemeService from './../../services/ThemeService';
    import { DefaultLocalStorageManager } from './../../services/LocalStorageManagerService'
    export default {
        props: {
            value : Boolean,
        },
        emits: ['input'],
        data: function() {
            return {
                selectedTheme: ThemeService.getCurrentTheme(),
                displayNames: {
                    auto: {
                        name: 'Auto',
                        icon: 'mdi-theme-light-dark'
                    },
                    light: {
                        name: 'Light theme',
                        icon: 'mdi-weather-sunny'
                    },
                    dark: {
                        name: 'Dark theme',
                        icon: 'mdi-weather-night'
                    },
                    eink: {
                        name: 'Eink theme',
                        icon: 'mdi-tablet'
                    }
                },
            };
        },
        mounted: function() {
        },
        methods: {
            selectTheme: function(newTheme) {
                // switch to user's system theme if 'auto' is selected
                if (newTheme === 'auto') {
                    DefaultLocalStorageManager.saveSetting('theme-auto', true);
                    newTheme = ThemeService.getAutoTheme()
                } else {
                    DefaultLocalStorageManager.saveSetting('theme-auto', false);
                }

                DefaultLocalStorageManager.saveSetting('theme', newTheme);
                this.$cookie?.set('theme', newTheme);
                ThemeService.setDefaultVuetifyTheme(this.$vuetify);
                ThemeService.setVuetifyTheme(this.$vuetify, this.$store)

                this.close();
                window.location.reload();

            },
            close: function() {
                this.updateValue(false);
            }
        }
    }
</script>

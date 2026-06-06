<template>
    <v-container>
        <v-tabs v-model="tab" bg-color="foreground" class="admin-settings-tabs user-settings-tabs rounded-lg border overflow-hidden">
            <v-tab :value="0">Account</v-tab>
            <v-tab :value="1">Theme</v-tab>
        </v-tabs>
        <v-window v-model="tab" id="admin-tab-items" elevation="0" class="no-background rounded-lg mt-4 pa-6">
            <v-window-item :value="0">
                <user-settings-account :language="$props.language" />
            </v-window-item>
            <v-window-item :value="1">
                <user-settings-themes />
            </v-window-item>
        </v-window>
    </v-container>
</template>

<script>
    export default {
        data: function() {
            return {
                tab: 0,
            }
        },
        props: {
            language: String
        },
        mounted() {
        },
        methods: {
        }
    }
</script>

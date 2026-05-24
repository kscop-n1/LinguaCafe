<template>
    <v-container v-if="$store.getters['shared/userAdmin']">
        <v-tabs v-model="tab" background-color="foreground" class="rounded-lg border overflow-hidden" @change="tabChanged">
            <v-tab>Dashboard</v-tab>
            <v-tab>Users</v-tab>
            <v-tab>Languages</v-tab>
            <v-tab>Dictionaries</v-tab>
            <v-tab>Fonts</v-tab>
            <v-tab>API</v-tab>
            <v-tab>Reviews</v-tab>
        </v-tabs>
        <v-window v-model="tab" id="admin-tab-items" elevation="0" class="no-background rounded-lg mt-4 pa-6">
            <v-window-item :value="0">
                <admin-dashboard></admin-dashboard>
            </v-window-item>
            <v-window-item :value="1">
                <admin-user-settings></admin-user-settings>
            </v-window-item>
            <v-window-item :value="2">
                <admin-language-settings></admin-language-settings>
            </v-window-item>
            <v-window-item :value="3">
                <admin-dictionary-settings :language="$props.language"></admin-dictionary-settings>
            </v-window-item>
            <v-window-item :value="4">
                <admin-font-type-settings></admin-font-type-settings>
            </v-window-item>
            <v-window-item :value="5">
                <admin-api-settings></admin-api-settings>
            </v-window-item>
            <v-window-item :value="6">
                <admin-review-settings></admin-review-settings>
            </v-window-item>
        </v-window>
    </v-container>
    <v-container v-else>
        You do not have permission to access this page.
    </v-container>
</template>

<script>
    export default {
        data: function() {
            return {
                tab: 0,
                loading: true,
                tabIndexes: {
                    'dashboard': 0,
                    'users': 1,
                    'languages': 2,
                    'dictionaries': 3,
                    'font-types': 4,
                    'api': 5,
                    'reviews': 6,
                },
                tabUrls: [
                    'dashboard',
                    'users',
                    'languages',
                    'dictionaries',
                    'font-types',
                    'api',
                    'reviews',
                ]
            }
        },
        props: {
            language: String,
        },
        beforeMount() {
        },
        mounted() {
            if (this.$route.params.page !== undefined) {
                this.tab = this.tabIndexes[this.$route.params.page];
            } 
        },
        methods: {
            tabChanged(event) {
                var page = this.tabUrls[event];

                if (this.$route.path !== '/admin/' + page) {
                    this.$router.push('/admin/' + page);
                }
            }
        }
    }
</script>

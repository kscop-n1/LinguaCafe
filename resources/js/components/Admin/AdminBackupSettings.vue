<template>
    <div id="admin-backup-settings" v-if="settings">
        <v-form v-model="isFormValid">
            <!-- Database backup settings -->
            <div class="subheader mt-4 mb-4 px-2">Backups</div>
            <v-card outlined class="rounded-lg pa-4 pb-0 pt-0">
                <v-card-text class="p-0">
                    <!-- Database backup compression label -->
                    <label class="font-weight-bold mt-4 mb-0">
                        Database backup compression

                        <!-- Database backup compression info box -->
                        <v-menu offset-y nudge-top="-12px">
                            <template v-slot:activator="{ on, attrs }">
                                <v-icon class="ml-1" v-bind="attrs" v-on="on"
                                    >mdi-help-circle-outline</v-icon
                                >
                            </template>
                            <v-card outlined class="rounded-lg pa-4" width="320px">
                                This option will force future database backups to be compressed into
                                a .zip format to save disk space. Existing backups will remain until
                                the number of backups exceeds the retention limit.
                            </v-card>
                        </v-menu>
                    </label>

                    <!-- Database backup compression toggle -->
                    <v-switch
                        v-model="settings.backupCompression"
                        class="mt-0"
                        color="primary"
                        hide-hints
                        dense
                        label="Enable compression (.zip)"
                    ></v-switch>
                </v-card-text>
            </v-card>

            <!-- Save result alerts -->
            <v-alert
                v-if="!saving && saveStatus !== '' && saveStatus !== 'success'"
                class="rounded-lg my-3"
                color="error"
                type="error"
                border="left"
                dark
            >
                An error has occurred while saving API settings.
            </v-alert>

            <!-- Save button -->
            <div class="d-flex">
                <v-spacer />
                <v-btn
                    rounded
                    :class="{ 'my-2': saving || saveStatus == '' || saveStatus == 'success' }"
                    color="primary"
                    @click="saveSettings"
                    :disabled="saving || !isFormValid"
                    :loading="saving"
                >
                    Save settings
                </v-btn>
            </div>
        </v-form>
    </div>
</template>

<script>
export default {
    data: function () {
        return {
            isFormValid: false,
            settings: null,
            saving: false,
            saveStatus: '',
            rules: {
                notEmpty: value => {
                    if (!value.length) {
                        return 'Field cannot be empty.'
                    }

                    return true
                },
            },
        }
    },
    props: {
        language: String,
    },
    mounted() {
        this.loadSettings()
    },
    methods: {
        loadSettings() {
            axios
                .post('/settings/global/get', {
                    settingNames: ['backupCompression'],
                })
                .then(result => {
                    this.settings = result.data
                })
        },
        saveSettings() {
            this.saving = true

            axios
                .post('/settings/global/update', {
                    settings: {
                        backupCompression: this.settings.backupCompression,
                    },
                })
                .catch(error => {
                    this.saving = false
                    this.saveStatus = 'error'
                })
                .then(response => {
                    if (response.status !== 200) {
                        return
                    }

                    this.saving = false
                    this.saveStatus = 'success'
                })
        },
    },
}
</script>

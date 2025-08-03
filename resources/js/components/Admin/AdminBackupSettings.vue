<template>
    <div id="admin-backup-settings" v-if="settings">
        <v-form v-model="isFormValid">
            <!-- Database backup settings -->
            <div class="subheader mt-4 mb-4">Database backups</div>
            <v-card outlined class="rounded-lg p-3">
                <v-card-text>
                    <!-- Database backup compression label -->
                    <label class="font-weight-bold">
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

                <!-- Database schedule settings -->
                    <!-- Database backup schedule label -->
                    <label class="font-weight-bold">
                        Database backup schedule

                        <!-- Database backup schedule info box -->
                        <v-menu offset-y nudge-top="-12px">
                            <template v-slot:activator="{ on, attrs }">
                                <v-icon class="ml-1" v-bind="attrs" v-on="on"
                                    >mdi-help-circle-outline</v-icon
                                >
                            </template>
                            <v-card outlined class="rounded-lg pa-4" width="320px">
                                This option configures how often backups of the database are taken using the <a href="https://en.wikipedia.org/wiki/Cron#Overview">Cron</a> format.
                            </v-card>
                        </v-menu>
                    </label>
                    <v-text-field
                        class="font-mono"
                        v-model="settings.backupInterval"
                        filled
                        dense
                        rounded
                        hide-details
                        maxlength=75
                        placeholder="0,30 * * * *"
                        :disabled="saving"
                        :rules="[rules.notEmpty, rules.validCron]"
                    ></v-text-field>
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

                <div v-if="saveErrorMsg.message">{{saveErrorMsg.message}}</div> 
                <div v-else>An error has occurred while saving backup settings. {{saveErrorMsg}}</div>
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
            saveErrorMsg: '',
            rules: {
                notEmpty: value => {
                    return value?.length ? true : 'Field cannot be empty.'
                },
                validCron: value => {
                    let cronRegex = /^((((\d+,)+\d+|(\d+(\/|-|#)\d+)|\d+L?|\*(\/\d+)?|L(-\d+)?|\?|[A-Z]{3}(-[A-Z]{3})?) ?){5,7})$/;
                    return value.match(cronRegex) ? true : 'The schedule must be a valid CRON entry.'
                }
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
                    settingNames: ['backupCompression', 'backupInterval'],
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
                        backupInterval: this.settings.backupInterval,
                    },
                })
                .catch(error => {
                    this.saving = false
                    this.saveStatus = 'error'
                    this.saveErrorMsg = error.response?.data
                })
                .then(response => {
                    if (response?.status !== 200) {
                        return
                    }

                    this.saving = false
                    this.saveStatus = 'success'
                    this.saveErrorMsg = ''
                })
        },
    },
}
</script>

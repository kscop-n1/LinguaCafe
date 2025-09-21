<template>
    <div id="admin-backup-settings" v-if="settings">
        <v-form v-model="isFormValid">
            <!-- Database backup settings -->

            <div class="subheader mt-4">Backup Retention</div>
            <v-card outlined class="rounded-lg p-3">
                <v-card-text id="backup-retention-body">
                    <div>
                        <!-- Database backup most recenct label -->
                        <label class="font-weight-bold">
                            Keep Last

                            <v-menu>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-icon class="ml-1" v-bind="attrs" v-on="on"
                                        >mdi-help-circle-outline</v-icon
                                    >
                                </template>
                                <v-card outlined class="rounded-lg pa-4" width="320px">
                                    This option configures how many of the most recent backups
                                    should be kept.
                                </v-card>
                            </v-menu>
                        </label>

                        <!-- Database backup most recent input -->
                        <v-text-field
                            v-model="settings.backupRetainMostRecent"
                            filled
                            dense
                            rounded
                            hide-details
                            maxlength="2"
                            placeholder="5"
                            :disabled="saving"
                            :rules="[rules.isInteger]"
                        ></v-text-field>
                    </div>

                    <div>
                        <!-- Database backup daily label -->
                        <label class="font-weight-bold">
                            Keep Daily

                            <v-menu>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-icon class="ml-1" v-bind="attrs" v-on="on"
                                        >mdi-help-circle-outline</v-icon
                                    >
                                </template>
                                <v-card outlined class="rounded-lg pa-4" width="320px">
                                    This option configures how many backups should be kept over the
                                    last "X" number of days. For example, a setting of 14 will keep
                                    at least one backup for each day of the last two weeks.
                                </v-card>
                            </v-menu>
                        </label>

                        <!-- Database backup daily max input -->
                        <v-text-field
                            v-model="settings.backupRetainDaily"
                            filled
                            dense
                            rounded
                            hide-details
                            maxlength="2"
                            placeholder="5"
                            :disabled="saving"
                            :rules="[rules.isInteger]"
                        ></v-text-field>
                    </div>

                    <div>
                        <!-- Database backup weekly label -->
                        <label class="font-weight-bold">
                            Keep Weekly

                            <v-menu>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-icon class="ml-1" v-bind="attrs" v-on="on"
                                        >mdi-help-circle-outline</v-icon
                                    >
                                </template>
                                <v-card outlined class="rounded-lg pa-4" width="320px">
                                    This option configures how many backups should be kept over the
                                    last "X" number of weeks. For example, a setting of 4 will keep
                                    at least one backup for each of the last 4 weeks.
                                </v-card>
                            </v-menu>
                        </label>

                        <!-- Database backup weekly max input -->
                        <v-text-field
                            v-model="settings.backupRetainWeekly"
                            filled
                            dense
                            rounded
                            hide-details
                            maxlength="2"
                            placeholder="4"
                            :disabled="saving"
                            :rules="[rules.isInteger]"
                        ></v-text-field>
                    </div>

                    <div>
                        <!-- Database backup monthly label -->
                        <label class="font-weight-bold">
                            Keep Monthly

                            <v-menu>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-icon class="ml-1" v-bind="attrs" v-on="on"
                                        >mdi-help-circle-outline</v-icon
                                    >
                                </template>
                                <v-card outlined class="rounded-lg pa-4" width="320px">
                                    This option configures how many backups should be kept over the
                                    last "X" number of months. For example, a setting of 12 will
                                    keep at least one backup for each month of the last year.
                                </v-card>
                            </v-menu>
                        </label>

                        <!-- Database backup monthly max input -->
                        <v-text-field
                            v-model="settings.backupRetainMonthly"
                            filled
                            dense
                            rounded
                            hide-details
                            maxlength="2"
                            placeholder="6"
                            :disabled="saving"
                            :rules="[rules.isInteger]"
                        ></v-text-field>
                    </div>

                    <div>
                        <!-- Database backup yearly label -->
                        <label class="font-weight-bold">
                            Keep Yearly

                            <v-menu>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-icon class="ml-1" v-bind="attrs" v-on="on"
                                        >mdi-help-circle-outline</v-icon
                                    >
                                </template>
                                <v-card outlined class="rounded-lg pa-4" width="320px">
                                    This option configures how many backups should be kept over the
                                    last "X" number of years. For example, a setting of 10 will keep
                                    at least one backup for each year of the last decade.
                                </v-card>
                            </v-menu>
                        </label>

                        <!-- Database backup yearly max input -->
                        <v-text-field
                            v-model="settings.backupRetainYearly"
                            filled
                            dense
                            rounded
                            hide-details
                            maxlength="2"
                            placeholder="5"
                            :disabled="saving"
                            :rules="[rules.isInteger]"
                        ></v-text-field>
                    </div>
                </v-card-text>
            </v-card>

            <div class="subheader mt-4">Miscellaneous</div>
            <v-card outlined class="rounded-lg p-3">
                <v-card-text>
                    <!-- Database backup compression label -->
                    <label class="font-weight-bold mt-4 mb-0">
                        Database backup compression

                        <!-- Database backup compression info box -->
                        <v-menu>
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
                <div v-if="saveErrorMsg.message">{{ saveErrorMsg.message }}</div>
                <div v-else>
                    An error has occurred while saving backup settings. {{ saveErrorMsg }}
                </div>
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
                isInteger: value => {
                    return Number.isInteger(parseInt(value))
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
                .post('/api/admin/settings', {
                    settingNames: [
                        'backupCompression',
                        'backupRetainDaily',
                        'backupRetainWeekly',
                        'backupRetainMonthly',
                        'backupRetainYearly',
                        'backupRetainMostRecent',
                    ],
                })
                .then(result => {
                    this.settings = result.data.data
                })
        },
        saveSettings() {
            this.saving = true

            axios
                .post('/api/admin/settings/update', {
                    settings: {
                        backupCompression: this.settings.backupCompression,
                        backupRetainDaily: parseInt(this.settings.backupRetainDaily),
                        backupRetainWeekly: parseInt(this.settings.backupRetainWeekly),
                        backupRetainMonthly: parseInt(this.settings.backupRetainMonthly),
                        backupRetainYearly: parseInt(this.settings.backupRetainYearly),
                        backupRetainMostRecent: parseInt(this.settings.backupRetainMostRecent),
                    },
                })
                .then(response => {
                    this.saving = false
                    this.saveStatus = 'success'
                    this.saveErrorMsg = ''
                })
                .catch(error => {
                    this.saving = false
                    this.saveStatus = 'error'
                })
        },
    },
}
</script>

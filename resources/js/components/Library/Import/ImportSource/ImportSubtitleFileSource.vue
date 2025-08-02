<template>
    <div class="d-flex flex-column align-stretch">
        <!-- Subtitle file -->
        <label class="font-weight-bold">Subtitle file</label>
        <v-file-input
            v-model="subtitleFile"
            filled
            dense
            rounded
            persistent-hint
            hint="Accepted formats: SubStation Alpha (.ssa and .ass), SubRip (.srt), MPL2 (.mpl2), MicroDVD (.sub), TMP (.tmp), WebVTT (.vtt), TTML (.ttml), SAMI (.sami)"
            ref="subtitleFile"
            placeholder="Subtitle file"
            prepend-icon="mdi-book"
            class="mb-4"
            :rules="[rules.subtitleFileRule]"
            @change="subtitleFileSelected"
        ></v-file-input>

        <!-- Subtitle content loading -->
        <div class="d-flex justify-center">
            <v-progress-circular v-if="loading" indeterminate color="primary"></v-progress-circular>
        </div>

        <!-- Error -->
        <v-alert v-if="!loading && error" border="left" type="error">
            An error has occurred while retrieving the contents of the subtitle file. Please try
            again, and if the error does not get resolved, create a GitHub issue.
        </v-alert>
    </div>
</template>

<script>
import axios from 'axios'

export default {
    data: function () {
        return {
            subtitles: null,
            subtitleFile: null,
            isFormValid: false,
            loading: false,
            error: false,
            rules: {
                subtitleFileRule: value => {
                    if (value === null || value === undefined) {
                        return 'You must select a file.'
                    }

                    return true
                },
            },
        }
    },
    props: {
        language: String,
    },
    mounted() {},
    methods: {
        subtitleFileSelected() {
            // validate
            this.subtitles = null
            this.error = false

            if (!this.$refs.subtitleFile.validate()) {
                // disable continue button in import dialog
                this.$emit('subtitle-selected', {
                    subtitles: null,
                    isImportSourceValid: false,
                })

                return
            }

            this.loading = true
            var formData = new FormData()
            formData.set('subtitleFile', this.subtitleFile)

            axios
                .post('/subtitle/get-subtitle-file-content', formData)
                .then(response => {
                    this.subtitles = response.data
                    this.loading = false

                    this.$emit('subtitle-selected', {
                        subtitles: this.subtitles,
                        isImportSourceValid: true,
                    })
                })
                .catch(error => {
                    this.subtitles = null
                    this.error = true
                    this.loading = false

                    this.$emit('subtitle-selected', {
                        subtitles: this.subtitles,
                        isImportSourceValid: false,
                    })
                })
        },
    },
}
</script>

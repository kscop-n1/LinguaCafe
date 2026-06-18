<template>
    <v-dialog :model-value="dialogValue" @update:model-value="updateValue" persistent max-width="1000">
        <v-card
            id="text-reader-settings"
            variant="outlined"
            class="rounded-lg app-dialog-card"
        >
            <v-card-title>
                <span class="text-h5">Settings</span>
                <v-spacer></v-spacer>
                <v-btn icon @click="close">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>
            <v-card-text class="pb-12" v-if="settingsLoaded">
                <v-tabs :show-arrows="$vuetify.display.smAndDown" v-model="tab" bg-color="foreground" class="rounded-lg border overflow-hidden">
                    <v-tab>Text</v-tab>
                    <v-tab>Vocabulary box</v-tab>
                    <v-tab>Vocabulary hover box</v-tab>
                </v-tabs>
                <v-window v-model="tab" elevation="0" class="rounded-lg mt-4 pa-6">
                    <!-- Text section -->
                    <v-window-item :value="0">
                        <!-- Font type -->
                        <v-row v-if="fontTypes.length" class="settings-row settings-row--select">
                            <v-col cols="12" md="4" class="settings-row__label">Font type:</v-col>
                            <v-col cols="12" md="8" class="settings-row__control">
                                <v-select
                                    v-model="selectedFontType"
                                    :items="fontTypes"
                                    item-title="name"
                                    item-value="id"
                                    density="compact"
                                    rounded
                                    variant="filled"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-select>
                            </v-col>
                        </v-row>

                        <!-- Line spacing -->
                        <v-row class="settings-row settings-row--slider">
                            <v-col cols="12" sm="3" class="settings-row__label">Space between lines:</v-col>
                            <v-col class="settings-row__control">
                                <v-slider
                                    v-model="settings.lineSpacing"
                                    :tick-labels="['Small', '', '', '', '', '', '', '', '', '', 'Large']"
                                    :tick-size="0"
                                    :max="10"
                                    thumb-label="always"
                                    thumb-size="20"
                                    hide-details
                                    step="1"
                                    track-color="#c5c5c5"
                                    @update:model-value="saveSettings"
                                >
                                </v-slider>
                            </v-col>
                        </v-row>

                        <!-- Maximum text width -->
                        <v-row class="settings-row settings-row--slider">
                            <v-col cols="12" sm="3" class="settings-row__label">Maximum text width:</v-col>
                            <v-col class="settings-row__control">
                                <v-slider
                                    v-model="settings.maximumTextWidth"
                                    :tick-labels="['Small', '', '', '', '', '', 'Full']"
                                    :tick-size="0"
                                    :max="6"
                                    thumb-label="always"
                                    thumb-size="20"
                                    hide-details
                                    step="1"
                                    track-color="#c5c5c5"
                                    @update:model-value="saveSettings"
                                >
                                    <template v-slot:thumb-label>{{ maximumTextWidthData[settings.maximumTextWidth] }}</template>
                                </v-slider>
                            </v-col>
                        </v-row>

                        <!-- Font size -->
                        <v-row class="settings-row settings-row--slider">
                            <v-col cols="12" sm="3" class="settings-row__label">Font size:</v-col>
                            <v-col class="settings-row__control">
                                <v-slider
                                    v-model="settings.fontSize"
                                    :tick-labels="['Small', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Large']"
                                    :tick-size="0"
                                    :min="12"
                                    :max="30"
                                    step="1"
                                    thumb-label="always"
                                    thumb-size="20"
                                    hide-details
                                    track-color="#c5c5c5"
                                    @update:model-value="saveSettings"
                                ></v-slider>
                            </v-col>
                        </v-row>

                        <!-- Hide all highlighting -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label">Hide all highlighting:</v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.hideAllHighlights"
                                    hide-details
                                    @update:model-value="saveSettings('hideAllHighlights')"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Hide new word highlighting -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label">Hide new word highlighting:</v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.hideNewWordHighlights"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Vertical text -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label">Vertical text:</v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.verticalText"
                                    hide-details
                                    @update:model-value="saveSettings"
                                    disabled
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Furigana on highlighted words -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label">Furigana on highlighted words:</v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.furiganaOnHighlightedWords"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Furigana on new words -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label">Furigana on new words:</v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.furiganaOnNewWords"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Auto move words to known -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label settings-label-with-help">Auto move words to known:
                                <v-menu location="bottom end" :offset="[0, -12]">
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon variant="text" size="small" class="settings-row__help" v-bind="props" aria-label="About auto move words to known">
                                            <v-icon size="small">mdi-help-circle-outline</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-card variant="outlined" class="rounded-lg pa-4" width="320px">
                                        Clicking the <b>Finish reading</b> button moves new words to known.
                                    </v-card>
                                </v-menu>
                            </v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.autoMoveWordsToKnown"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Auto highlight words -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label settings-label-with-help">Auto highlight words:
                                <v-menu location="bottom end" :offset="[0, -12]">
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon variant="text" size="small" class="settings-row__help" v-bind="props" aria-label="About auto highlight words">
                                            <v-icon size="small">mdi-help-circle-outline</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-card variant="outlined" class="rounded-lg pa-4" width="320px">
                                        Auto highlight words when you add a translation to them.
                                    </v-card>
                                </v-menu>
                            </v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.autoHighlightWords"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Auto level up words -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label settings-label-with-help">Auto level up words:
                                <v-menu location="bottom end" :offset="[0, -12]">
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon variant="text" size="small" class="settings-row__help" v-bind="props" aria-label="About auto level up words">
                                            <v-icon size="small">mdi-help-circle-outline</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-card variant="outlined" class="rounded-lg pa-4" width="320px">
                                        Clicking the 'Finished reading' button will automatically level up any words and phrases for which you haven't opened the vocabulary box.
                                    </v-card>
                                </v-menu>
                            </v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.autoLevelUpWords"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Show subtitle timestamps -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label">Show subtitle timestamps:</v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.showSubtitleTimestamps"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Space between subtitles -->
                        <v-row class="settings-row settings-row--slider">
                            <v-col cols="12" sm="3" class="settings-row__label">Space between subtitles:</v-col>
                            <v-col class="settings-row__control">
                                <v-slider
                                    v-model="settings.spaceBetweenSubtitles"
                                    :tick-labels="['Small', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Large']"
                                    :tick-size="0"
                                    :min="0"
                                    :max="40"
                                    step="2"
                                    thumb-label="always"
                                    thumb-size="20"
                                    hide-details
                                    track-color="#c5c5c5"
                                    @update:model-value="saveSettings"
                                ></v-slider>
                            </v-col>
                        </v-row>

                        <!-- Text to speech section -->
                        <div class="subheader subheader-margin-top d-flex mb-2" v-if="textToSpeechVoices.length">
                            Text to speech
                        </div>

                        <!-- Text to speech -->
                        <v-row v-if="textToSpeechVoices.length" class="settings-row settings-row--select">
                            <v-col cols="12" md="4" class="settings-row__label">TTS voice:</v-col>
                            <v-col cols="12" md="8" class="settings-row__control">
                                <v-select
                                    v-model="textToSpeechSelectedVoice"
                                    :items="textToSpeechVoices"
                                    item-title="name"
                                    item-value="name"
                                    density="compact"
                                    rounded
                                    variant="filled"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-select>
                            </v-col>
                        </v-row>

                        <!-- Text to speech speed -->
                        <v-row v-if="textToSpeechVoices.length" class="settings-row settings-row--slider">
                            <v-col cols="12" md="4" class="settings-row__label">TTS speed:</v-col>
                            <v-col cols="12" md="8" class="settings-row__control">
                                <v-slider
                                    v-model="settings.textToSpeechSpeed"
                                    :tick-labels="['0.3', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '2']"
                                    :tick-size="0"
                                    :max="2"
                                    :min="0.3"
                                    thumb-label="always"
                                    thumb-size="20"
                                    hide-details
                                    step="0.1"
                                    track-color="#c5c5c5"
                                    class="align-center"
                                    @update:model-value="saveSettings"
                                />
                            </v-col>
                        </v-row>
                    </v-window-item>

                    <!-- Vocabulary box section-->
                    <v-window-item :value="1">
                        <!-- Vocab box scroll into view -->
                        <v-row class="settings-row settings-row--select">
                            <v-col cols="12" md="4" class="settings-row__label">Scroll to vocabulary method:</v-col>
                            <v-col cols="12" md="8" class="settings-row__control">
                                <v-select
                                    v-model="settings.vocabBoxScrollIntoView"
                                    :items="vocabBoxScrollIntoViewData"
                                    item-title="name"
                                    item-value="value"
                                    density="compact"
                                    rounded
                                    variant="filled"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-select>
                            </v-col>
                        </v-row>

                        <!-- Vocabulary sidebar -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label settings-label-with-help">
                                Vocabulary sidebar:
                                <v-menu location="bottom end" :offset="[0, -12]">
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon variant="text" size="small" class="settings-row__help" v-bind="props" aria-label="About vocabulary sidebar">
                                            <v-icon size="small">mdi-help-circle-outline</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-card variant="outlined" class="rounded-lg pa-4" width="320px">
                                        An always visible sidebar vocabulary in a fixed position, that replaces the popup vocabulary. <br><br>
                                        This option is only available for devices with at least 960px screen width, and it is also only available in subtitle reader if the media controls are hidden.
                                    </v-card>
                                </v-menu>
                            </v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.vocabularySidebar"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Vocabulary bottom sheet -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label settings-label-with-help">
                                Vocabulary bottom sheet:
                                <v-menu location="bottom end" :offset="[0, -12]">
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon variant="text" size="small" class="settings-row__help" v-bind="props" aria-label="About vocabulary bottom sheet">
                                            <v-icon size="small">mdi-help-circle-outline</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-card variant="outlined" class="rounded-lg pa-4" width="320px">
                                        A bottom sheet vocabulary designed for mobile screens, that replaces the popup vocabulary. <br><br>
                                        This option is only available for devices with less than or equal to 768px screen width.
                                    </v-card>
                                </v-menu>
                            </v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.vocabularyBottomSheet"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>
                    </v-window-item>

                    <!-- Vocabulary hover box section-->
                    <v-window-item :value="2">
                        <!-- Vocabulary hover box -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label settings-label-with-help">Hover vocabulary box:
                                <v-menu location="bottom end" :offset="[0, -12]">
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon variant="text" size="small" class="settings-row__help" v-bind="props" aria-label="About hover vocabulary box">
                                            <v-icon size="small">mdi-help-circle-outline</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-card variant="outlined" class="rounded-lg pa-4" width="320px">
                                        A minimalistic vocabulary box that appears when you move the mouse over a word or phrase.
                                    </v-card>
                                </v-menu>
                            </v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    color="primary"
                                    v-model="settings.vocabularyHoverBox"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Hover vocabulary box dictionary search -->
                        <v-row class="settings-row settings-row--toggle">
                            <v-col cols="8" md="4" class="settings-row__label">Hover vocabulary dictionary search:</v-col>
                            <v-col cols="4" md="8" class="settings-row__control">
                                <v-switch
                                    v-model="settings.vocabularyHoverBoxSearch"
                                    color="primary"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-switch>
                            </v-col>
                        </v-row>

                        <!-- Hover vocabulary delay -->
                        <v-row class="settings-row settings-row--slider">
                            <v-col cols="12" sm="3" class="settings-row__label">Hover vocabulary delay:</v-col>
                            <v-col class="settings-row__control">
                                <v-slider
                                    v-model="settings.vocabularyHoverBoxDelay"
                                    :tick-labels="['200ms', '', '', '', '', '', '', '', '1000ms']"
                                    :tick-size="0"
                                    :min="200"
                                    :max="1000"
                                    thumb-label="always"
                                    thumb-size="20"
                                    hide-details
                                    step="100"
                                    track-color="#c5c5c5"
                                    @update:model-value="saveSettings"
                                >
                                </v-slider>
                            </v-col>
                        </v-row>

                        <!-- Hover vocabulary preferred position -->
                        <v-row class="settings-row settings-row--select">
                            <v-col cols="12" md="4" class="settings-row__label">Preferred position:</v-col>
                            <v-col cols="12" md="8" class="settings-row__control">
                                <v-select
                                    v-model="settings.vocabularyHoverBoxPreferredPosition"
                                    :items="vocabularyHoverBoxPreferredPositionData"
                                    item-title="name"
                                    item-value="value"
                                    density="compact"
                                    rounded
                                    variant="filled"
                                    hide-details
                                    @update:model-value="saveSettings"
                                ></v-select>
                            </v-col>
                        </v-row>
                    </v-window-item>
                </v-window>

            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn rounded color="primary" @click="close">Close</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
    import TextToSpeechService from './../../services/TextToSpeechService';
    import FontTypeService from './../../services/FontTypeService';
    import {  defaultSettings, DefaultLocalStorageManager } from './../../services/LocalStorageManagerService';

    export default {
        emits: ['update:modelValue'],
        data: function() {
            return {
                /*
                    Text to speech and font type settings are handled differently,
                    because they are a separate setting for every language.
                */
            fontTypeService: new FontTypeService(this.$props.language, this.fontTypesLoaded),
            fontTypes: [],
            selectedFontType: null,
            textToSpeechService: new TextToSpeechService(this.$props.language, this.textToSpeechVoicesChanged),
            textToSpeechVoices: [],
            textToSpeechSelectedVoice: null,

            tab: 0,
            settingsLoaded: false,
            settings: { ...defaultSettings },
            vocabularyHoverBoxPreferredPositionData: [
                {
                    name: 'Below the hovered word',
                    value: 'bottom'
                },
                {
                    name: 'Above the hovered word',
                    value: 'top'
                },
            ],
            vocabBoxScrollIntoViewData: [
                {
                    name: 'Disabled',
                    value: 'disabled'
                },
                {
                    name: 'Scroll into view',
                    value: 'scroll-into-view'
                },
                {
                    name: 'Scroll into view if needed (does not work everywhere)',
                    value: 'scroll-into-view-if-needed'
                }
            ],
            maximumTextWidthData: ['800px', '900px', '1000px', '1200px', '1400px', '1600px', '100%'],
            }
        },
        props: {
            modelValue: Boolean,
            language: String,
        },
        mounted() {
            this.settings = DefaultLocalStorageManager.loadAndParseSettings(this.settings);
            this.settingsLoaded = true;
            this.saveSettings();

            this.textToSpeechVoicesChanged();
        },
        methods: {
            fontTypesLoaded() {
                // set selected font
                this.selectedFontType = this.fontTypeService.getSelectedFontTypeId();

                // set font list
                this.fontTypes = this.fontTypeService.fonts;
            },
            textToSpeechVoicesChanged() {
                // set selected voice
                var selectedVoice = this.textToSpeechService.getSelectedVoice();
                if (selectedVoice !== null) {
                    this.textToSpeechSelectedVoice = selectedVoice.name;
                }

                // get list of voice
                this.textToSpeechVoices = this.textToSpeechService.getVoiceNames();
            },
            saveSettings(settingName = '') {
                if (settingName == 'hideAllHighlights') {
                    this.settings.hideNewWordHighlights = this.settings.hideAllHighlights;
                }

                if (this.settings.fontSize < 12) {
                    this.settings.fontSize = 12;
                }

                if (this.settings.fontSize > 30) {
                    this.settings.fontSize = 30;
                }

                DefaultLocalStorageManager.saveSettings(this.settings);

                // save text to speech
                if (this.textToSpeechSelectedVoice !== null) {
                    localStorage.setItem(`${this.$props.language}-text-to-speech-voice`, JSON.stringify(this.textToSpeechSelectedVoice));
                }

                // save font
                if (this.fontTypeService !== null && this.selectedFontType) {
                    this.fontTypeService.selectFontType(this.selectedFontType);
                    this.fontTypeService.loadSelectedFontTypeIntoDom(this.selectedFontType);
                }

                this.$emit('changed', this.settings);
                this.$forceUpdate();
            },
            saveSetting(name) {
                DefaultLocalStorageManager.saveSetting(name, this.settings[name]);
            },
            changeSetting(name, value, emitResult = false) {
                this.settings[name] = value

                if (this.settings.fontSize < 12) {
                    this.settings.fontSize = 12;
                }

                if (this.settings.fontSize > 30) {
                    this.settings.fontSize = 30;
                }

                ;
                this.saveSetting(name);

                if (emitResult) {
                    this.$emit('changed', this.settings);
                }
            },
            close(){
                this.updateValue(false);
            }
        }
    }
</script>

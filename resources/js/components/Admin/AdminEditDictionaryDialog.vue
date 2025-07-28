<template>
    <v-dialog v-model="value" persistent max-width="700px" height="400px">
        <v-card class="rounded-lg" :loading="loading || saveResult === 'saving'">
            <!-- Title -->
            <v-card-title>
                <v-icon class="mr-2">mdi-file-edit</v-icon>
                <span class="text-h5">Edit dictionary</span>
                <v-spacer></v-spacer>
                <v-btn icon @click="close">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>

            <!-- Content -->
            <v-card-text class="pt-4 pb-6" v-if="dictionary">

                <!-- Forms -->
                <template v-if="saveResult !== 'success'">
                    <!-- Host -->
                    <template v-if="dictionary.type === 'custom_api'">
                        <label class="font-weight-bold">API host</label>
                        <v-text-field 
                            v-model="dictionary.api_host"
                            filled
                            dense
                            rounded
                            placeholder="API host"
                        ></v-text-field>
                    </template>
                    
                    <!-- Name -->
                    <label class="font-weight-bold">Dictionary name</label>
                    <v-text-field 
                        v-model="dictionary.name"
                        filled
                        dense
                        rounded
                        :disabled="dictionary.name === 'JMDict'"
                        placeholder="Dictionary name"
                        maxlength="16"
                    ></v-text-field>

                    <!-- Source language -->
                    <label class="font-weight-bold">
                        Source language
                        
                        <!-- Source language info box -->
                        <v-menu offset-y nudge-top="-12px">
                            <template v-slot:activator="{ on, attrs }">
                                <v-icon class="ml-1" v-bind="attrs" v-on="on">mdi-help-circle-outline</v-icon>
                            </template>
                            <v-card outlined class="rounded-lg pa-4" width="320px">
                                The language that you are learning.
                            </v-card>
                        </v-menu>
                    </label>

                    <!-- Source language -->
                    <v-select
                        v-model="dictionary.source_language"
                        :items="languages"
                        item-value="name"
                        placeholder="Language"
                        dense
                        filled
                        rounded
                        disabled
                    >
                        <template v-slot:selection="{ item, index }">
                            <img class="mr-2 border" :src="'/images/flags/' + item.name + '.png'" width="40" height="26">
                            <span class="text-capitalize">{{ item.name }}</span>
                        </template>
                        <template v-slot:item="{ item }">
                            <img class="mr-2 border" :src="'/images/flags/' + item.name + '.png'" width="40" height="26">
                            <span class="text-capitalize">{{ item.name }}</span>
                        </template>
                    </v-select>

                    <!-- Target language -->
                    <label class="font-weight-bold">
                        Target language

                        <!-- Target language info box -->
                        <v-menu offset-y nudge-top="-12px">
                            <template v-slot:activator="{ on, attrs }">
                                <v-icon class="ml-1" v-bind="attrs" v-on="on">mdi-help-circle-outline</v-icon>
                            </template>
                            <v-card outlined class="rounded-lg pa-4" width="320px">
                                The language that the dictionary translates to. For example if it's a German -> English 
                                dictionary, you should select English as the target language. Target language has no function, 
                                it's just a visual help to arrange your dictionaries.
                            </v-card>
                        </v-menu>
                    </label>
                    
                    <v-select
                        v-model="dictionary.target_language"
                        :items="languages"
                        item-value="name"
                        placeholder="Language"
                        dense
                        filled
                        rounded
                        disabled
                    >
                        <template v-slot:selection="{ item, index }">
                            <img class="mr-2 border" :src="'/images/flags/' + item.name + '.png'" width="40" height="26">
                            <span class="text-capitalize">{{ item.name }}</span>
                        </template>
                        <template v-slot:item="{ item }">
                            <img class="mr-2 border" :src="'/images/flags/' + item.name + '.png'" width="40" height="26">
                            <span class="text-capitalize">{{ item.name }}</span>
                        </template>
                    </v-select>

                    <!-- Display color -->
                    <label class="font-weight-bold">Display color</label>
                    <v-menu
                        v-model="colorPicker"
                        width="290px"
                        offset-y
                        nudge-top="-10px"
                        right
                        :close-on-content-click="false"
                    >
                        <template v-slot:activator="{ on, attrs }">
                            <v-card
                                class="border"
                                outlined
                                :color="dictionary.color"
                                width="64px"
                                height="32px"
                                @click="colorPicker = !colorPicker;"
                            ></v-card>
                        </template>
                        <v-color-picker hide-inputs v-model="dictionary.color" />
                    </v-menu>
                    
                    <!-- Enabled -->
                    <label class="font-weight-bold mt-6">Enabled</label>
                    <v-switch
                        color="primary"
                        class="mt-0"
                        v-model="dictionary.enabled" 
                    ></v-switch>
                </template>

                <!-- Success message -->
                <v-alert
                    v-if="saveResult === 'success'"
                    class="rounded-lg"
                    color="success"
                    type="success"
                    border="left"
                    dark
                >
                    Dictionary saved successfully.
                </v-alert>

            </v-card-text>

            <!-- Action buttons -->
            <v-card-actions>
                <v-spacer></v-spacer>

                <!-- Buttons before successfull save -->
                <template v-if="saveResult !== 'success'">
                    <v-btn rounded text @click="close">Cancel</v-btn>
                    <v-btn 
                        rounded
                        depressed 
                        color="primary" 
                        :loading="saveResult === 'saving'" 
                        :disabled="saveResult === 'saving'" 
                        @click="save"
                    >Save</v-btn>
                </template>

                <!-- Buttons after successfull save -->
                <template v-if="saveResult === 'success'">
                    <v-btn rounded text @click="close">Close</v-btn>
                </template>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
    export default {
        props: {
            value : Boolean,
            dictionaryId: Number
        },
        emits: ['input'],
        data: function() {
            return {
                loading: true,
                saveResult: '',
                colorPicker: false,
                languages: [],
                dictionary: null,
            };
        },
        mounted: function() {
            axios.all([
                axios.get('/dictionaries/get/' + this.$props.dictionaryId),
                axios.get('/config/languages'),
            ]).then(axios.spread((response1, response2) => {
                this.loading = false;
                this.dictionary = response1.data.data;

                // add supported source languages
                response2.data.forEach((language) => {
                    this.languages.push({
                        name: language.name,
                        selected: false
                    });
                })
            }));
        },
        methods: {
            save() {
                this.saveResult = 'saving';
                axios.post(`/dictionaries/update/${this.dictionary.id}`, this.dictionary).then((response) => {
                    this.saveResult = 'success';
                    this.$emit('dictionary-saved');
                }).catch((error) => {
                    this.saveResult = 'error';
                });
            },
            close() {
                this.$emit('input', false);
            }
        }
    }
</script>

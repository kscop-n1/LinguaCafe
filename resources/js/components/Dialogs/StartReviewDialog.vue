<template>
    <v-dialog :model-value="dialogValue" @update:model-value="updateValue" persistent max-width="500px">
        <v-card class="rounded-lg">
            <v-card-title>
                <span class="text-h5">Review</span>
                <v-spacer></v-spacer>
                <v-btn icon @click="close">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>
            <v-card-text>
                <!-- Book name -->
                <span v-if="bookName !== ''">Book: {{ bookName }}</span><br>
                
                <!-- Chapter name -->
                <span v-if="chapterName !== ''">Chapter: {{ chapterName }}</span>

                <!-- Reviewing all words info -->
                <span v-if="bookName === '' && chapterName === ''">
                    Review cards from all of your books.
                </span>
            </v-card-text>
            <v-card-actions class="mt-8">
                
                <!-- Practice mode -->
                <v-checkbox class="mt-0 pt-0" hide-details v-model="practiceMode" label="Practice mode"></v-checkbox>
                <v-menu location="bottom end" :offset="[-88, -12]">
                    <template v-slot:activator="{ props }">
                        <v-icon class="ml-2" v-bind="props">mdi-help-circle-outline</v-icon>
                    </template>
                    <v-card variant="outlined" class="rounded-lg pa-4" width="252px">
                        <span class="mb-1">In practice mode:</span>
                        <ul class="mb-0">
                            <li>
                                Your words' and phrases' review due date will not change.
                            </li>
                            <li>
                                Your words' and phrases' level will not change.
                            </li>
                            <li>
                                Your reviews do not count in daily review goals.
                            </li>
                            <li>
                                You will also get cards which are due to a later date.
                            </li>
                        </ul>
                    </v-card>
                </v-menu>

                <v-spacer></v-spacer>
                <v-btn rounded text @click="close">Cancel</v-btn>
                <v-btn rounded text @click="startReview">Start</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
    export default {
        props: {
            value : Boolean,
            bookId: {
                type: Number,
                default: -1
            },
            bookName: {
                type: String,
                default: ''
            },
            chapterId: {
                type: Number,
                default: -1
            },
            chapterName: {
                type: String,
                default: ''
            }
        },
        watch: { 
            // reset practice mode when dialog opens
            value: function(newValue) {
                if (newValue) {
                    this.practiceMode = false;
                }
            }
        },
        emits: ['update:modelValue'],
        data: function() {
            return {
                practiceMode: false,
            };
        },
        mounted: function() {
        },
        methods: {
            startReview() {
                window.location.href = '/review/' + this.practiceMode + '/' + this.$props.bookId + '/' + this.$props.chapterId;
                this.updateValue(false);
            },
            close() {
                this.updateValue(false);
            }
        }
    }
</script>

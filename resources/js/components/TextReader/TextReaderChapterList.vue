<template>
    <v-dialog :model-value="dialogValue" @update:model-value="updateValue" scrollable persistent max-width="1000" attach=".v-main">
        <v-card
            id="text-reader-chapter-list"
            variant="outlined"
            class="rounded-lg"
        >
            <v-card-title>
                <span class="text-h5">Chapters</span>
                <v-spacer></v-spacer>
                <v-btn icon @click="close">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>
            <v-card-text class="pt-6 px-0">
                    <v-data-table
                        class="book-info-table no-hover pb-4 mx-auto"
                        :headers="headers"
                        :items="localChapters"
                        :loading="loading"
                        v-model:options="tableOptions"
                        :server-items-length="totalChapters"
                        :footer-props="{
                            'items-per-page-options': [25, 50, 100],
                        }"
                    >
                        <template v-slot:item.name="{ item }">
                            <span class="default-font">{{ item.name }}</span>
                        </template>

                        <template v-slot:item.wordCount.highlighted="{ item }">
                            <span class="rounded-pill highlighted">{{ item.wordCount.highlighted }}</span>
                        </template>

                        <template v-slot:item.wordCount.new="{ item }">
                            <span class="rounded-pill new">{{ item.wordCount.new }}</span>
                        </template>

                        <template v-slot:item.actions="{ item }">
                            <v-btn
                                v-if="item.id != currentChapterId && item.processing_status === 'processed'"
                                variant="flat"
                                rounded
                                small
                                color="primary"
                                width="80px"
                                :to="'/chapters/read/' + item.id"
                            >Read</v-btn>
                        </template>
                    </v-data-table>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn rounded color="primary" @click="close">Close</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
    export default {
        emits: ['update:modelValue'],
        data: function() {
            return {
                headers: [
                    { title: 'Name', key: 'name', align: 'center' },
                    { title: 'Words', key: 'wordCount.total', align: 'center' },
                    { title: 'Unique', key: 'wordCount.unique', align: 'center' },
                    { title: 'Highlighted', key: 'wordCount.highlighted', align: 'center' },
                    { title: 'New', key: 'wordCount.new', align: 'center' },
                    { title: 'Read', key: 'actions', align: 'center', sortable: false },
                ],
                localChapters: [],
                loading: false,
                totalChapters: 0,
                tableOptions: {
                    page: 1,
                    itemsPerPage: 50,
                    sortBy: [],
                    sortDesc: [],
                },
            }
        },
        props: {
            modelValue: Boolean,
            bookId: Number,
            currentChapterId: Number
        },
        watch: {
            modelValue(isOpen) {
                if (isOpen) {
                    this.loadChapters();
                }
            },
            tableOptions: {
                handler() {
                    this.loadChapters();
                },
                deep: true,
            },
        },
        methods: {
            close: function() {
                this.updateValue(false);
            },
            loadChapters() {
                if (!this.modelValue || !this.bookId) {
                    return;
                }

                this.loading = true;

                axios.post('/chapters', {
                    bookId: this.bookId,
                    page: this.tableOptions.page || 1,
                    perPage: this.tableOptions.itemsPerPage || 50,
                }).then((response) => {
                    this.localChapters = response.data.chapters;
                    this.totalChapters = response.data.total;
                    this.loadVisibleWordCounts();
                }).catch(() => {
                    this.loading = false;
                });
            },
            loadVisibleWordCounts() {
                const chapterIds = this.localChapters.map((chapter) => chapter.id);

                if (!chapterIds.length) {
                    this.loading = false;
                    return;
                }

                axios.get('/chapters/word-counts/' + this.bookId, {
                    params: {
                        chapterIds: chapterIds,
                    }
                }).then((response) => {
                    this.localChapters.forEach((chapter) => {
                        if (response.data[chapter.id] && response.data[chapter.id].wordCount) {
                            chapter.wordCount = response.data[chapter.id].wordCount;
                        }
                    });
                }).finally(() => {
                    this.loading = false;
                });
            }
        }
    }
</script>

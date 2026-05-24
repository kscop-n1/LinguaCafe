<template>
    <v-container class="book-chapters py-0">
        <!-- Error dialog -->
        <error-dialog
            v-if="errorDialog.active"
            v-model="errorDialog.active" 
            content="An error has occurred while deleting the chapter."
        ></error-dialog>

        <!-- Edit book chapter dialog -->
        <edit-book-chapter-dialog
            v-if="editBookChapterDialog.active"
            v-model="editBookChapterDialog.active" 
            :book-id="$props.bookId"
            :chapter-id="editBookChapterDialog.chapterId"
            @chapter-saved="chapterSaved"
        >
        </edit-book-chapter-dialog>

        <!-- Delete book chapter dialog -->
        <delete-book-chapter-dialog
            v-if="deleteBookChapterDialog.active"
            v-model="deleteBookChapterDialog.active" 
            :chapter-id="deleteBookChapterDialog.chapterId"
            :chapter-name="deleteBookChapterDialog.chapterName"
            @confirm="deleteChapter"
        >
        </delete-book-chapter-dialog>
        
        <!-- Review dialog -->
        <start-review-dialog 
            v-model="startReviewDialog.active" 
            :book-id="startReviewDialog.bookId" 
            :book-name="startReviewDialog.bookName"
            :chapter-id="startReviewDialog.chapterId" 
            :chapter-name="startReviewDialog.chapterName">
        </start-review-dialog>
        

        <!-- Chapter list -->
        <v-data-table
            class="my-4 mb-0 no-hover"
            :headers="[
                { text: 'Chapter', value: 'name'},
                { text: 'Read', value: 'read_count', align: 'center' },
                { text: 'Total', value: 'wordCount.total', align: 'center' },
                { text: 'Unique', value: 'wordCount.unique', align: 'center' },
                { text: 'Known', value: 'wordCount.known', align: 'center' },
                { text: 'Highlighted', value: 'wordCount.highlighted', align: 'center' },
                { text: 'New', value: 'wordCount.new', align: 'center' },
                { text: 'Actions', value: 'actions', sortable: false },
            ]"
            :items="chapters"
            :loading="chaptersLoading"
            v-model:options="tableOptions"
            :server-items-length="totalChapters"
            :footer-props="{
                'items-per-page-options': [25, 50, 100],
            }"
        >

            <!-- Read status -->
            <template v-slot:item.read_count="{ item }">
                <v-chip
                    v-if="item.read_count > 0"
                    small
                    color="success"
                    text-color="white"
                    title="Chapter has been finished at least once."
                >
                    <v-icon small left>mdi-check</v-icon>
                    {{ item.read_count }}
                </v-chip>
                <v-chip
                    v-else
                    small
                    outlined
                    color="grey"
                    title="Chapter has not been finished yet."
                >
                    <v-icon small left>mdi-book-open-page-variant</v-icon>
                    No
                </v-chip>
            </template>

            <!-- Total words -->
            <template v-slot:item.wordCount.total="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    {{ formatNumber(item.wordCount.total) }}
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Unique words -->
            <template v-slot:item.wordCount.unique="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    {{ formatNumber(item.wordCount.unique) }}
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Known words -->
            <template v-slot:item.wordCount.known="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    <template v-if="$props.wordCountDisplayType == 0">
                        {{ formatNumber(item.wordCount.known) }}
                    </template>
                    <template v-else-if="item.wordCount.unique">
                        {{ (item.wordCount.known / item.wordCount.unique * 100).toFixed(1) }}%
                    </template>
                    <template v-else>
                        0%
                    </template>
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Highlighted words -->
            <template v-slot:item.wordCount.highlighted="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    <div class="highlighted-words px-2 rounded-xl mx-auto">
                        <template v-if="$props.wordCountDisplayType < 2">
                            {{ formatNumber(item.wordCount.highlighted) }}
                        </template>
                        <template v-else-if="item.wordCount.unique">
                            {{ (item.wordCount.highlighted / item.wordCount.unique * 100).toFixed(1) }}%
                        </template>
                        <template v-else>
                            0%
                        </template>
                    </div>
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>


            <!-- New words -->
            <template v-slot:item.wordCount.new="{ item }">
                <!-- Count -->
                <template v-if="item.processing_status === 'processed' && item.wordCountsLoaded">
                    <div class="new-words px-2 rounded-xl mx-auto">
                        <template v-if="$props.wordCountDisplayType < 2">
                            {{ formatNumber(item.wordCount.new) }}
                        </template>
                        <template v-else-if="item.wordCount.unique">
                            {{ (item.wordCount.new / item.wordCount.unique * 100).toFixed(1) }}%
                        </template>
                        <template v-else>
                            0%
                        </template>
                    </div>
                </template>

                <!-- Skeleton -->
                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Actions -->
            <template v-slot:item.actions="{ item }">
                <div class="d-flex justify-center">
                    <!-- Action buttons -->
                    <template v-if="item.processing_status == 'processed'">
                        <v-btn icon :to="'/chapters/read/' + item.id" title="Read"><v-icon>mdi-book-open-variant</v-icon></v-btn>
                        <v-menu rounded offset-y bottom left nudge-top="-5">
                            <template v-slot:activator="{ props }">
                                <v-btn icon v-bind="props"><v-icon>mdi-dots-horizontal</v-icon></v-btn>
                            </template>
                            <v-btn
                                width="100"
                                class="menu-button"
                                tile
                                
                                @click="showEditChapterDialog(item.id)"
                            >
                                Edit
                            </v-btn>
                            <v-btn
                                width="100"
                                class="menu-button"
                                tile
                                
                                @click="showStartReviewDialog(book.id, book.name, item.id, item.name)"
                            >
                                Review
                            </v-btn>
                            <v-btn
                                width="100"
                                class="menu-button"
                                tile
                                
                                @click="showDeleteChapterDialog(item)"
                            >
                                Delete
                            </v-btn>
                        </v-menu>
                    </template>

                    <!-- Chapter importing loader -->
                    <template v-else-if="item.processing_status === 'unprocessed'">
                        <v-chip small color="warning">importing</v-chip>
                    </template>

                    <!-- Chapter importing failed -->
                    <template v-else-if="item.processing_status === 'failed'">
                        <v-chip small color="error">failed</v-chip>
                    </template>
                </div>
            </template>
        </v-data-table>
    </v-container>
</template>

<script>
    import {formatNumber} from './../../helper.js';
    export default {
        data: function() {
            return {
                book: null,
                bookWordCount: null,
                chapters: [],
                chaptersLoading: false,
                tableOptions: {
                    page: 1,
                    itemsPerPage: 50,
                    sortBy: [],
                    sortDesc: [],
                },
                totalChapters: 0,
                errorDialog: {
                    active: false,
                },
                editBookChapterDialog: {
                    active: false,
                    chapterId: -1,
                },
                deleteBookChapterDialog: {
                    active: false,
                    chapterId: -1,
                },
                startReviewDialog: {
                    active: false,
                    bookId: -1,
                    bookName: '',
                    chapterId: -1,
                    chapterName: '',
                }
            }
        },
        props: {
            bookId: Number,
            wordCountDisplayType: Number,
        },
        watch: {
            tableOptions: {
                handler() {
                    this.loadChapters();
                },
                deep: true,
                immediate: true,
            },
        },
        mounted() {
            // retrieve word counts
            this.$store.getters['shared/echo'].private('chapter-status-update.' + this.$store.getters['shared/userUuid']).listen('ChapterStateUpdatedEvent', (message) => {
                this.chapterStatusUpdate(JSON.parse(message.chapters));
            });
        },
        beforeUnmount() {
            this.$store.getters['shared/echo'].private('chapter-status-update.' + this.$store.getters['shared/userUuid']).stopListening('ChapterStateUpdatedEvent');
        },
        methods: {
            chapterStatusUpdate(chapters) {
                this.chapters.forEach((currentChapter) => {
                    if (!chapters[currentChapter.id]) {
                        return;
                    }

                    if ('wordCount' in chapters[currentChapter.id] && chapters[currentChapter.id].wordCount !== null) {
                        currentChapter.wordCount = chapters[currentChapter.id].wordCount
                        currentChapter.wordCountsLoaded = true;
                    }

                    if ('processing_status' in chapters[currentChapter.id]) {
                        currentChapter.processing_status = chapters[currentChapter.id].processing_status;
                    }
                });
            },
            chapterSaved() {
                this.$emit('word-count-changed');
            },
            showEditChapterDialog(chapterId) {
                this.editBookChapterDialog.active = true;
                this.editBookChapterDialog.chapterId = chapterId;
            },
            showDeleteChapterDialog(chapter) {
                this.deleteBookChapterDialog.active = true;
                this.deleteBookChapterDialog.chapterId = chapter.id;
                this.deleteBookChapterDialog.chapterName = chapter.name;
            },
            deleteChapter() {
                axios.post('/chapters/delete', {
                    'chapterId': this.deleteBookChapterDialog.chapterId,
                }).catch(() => {
                    this.errorDialog.active = true;
                }).then((response) => {
                    if (response.status === 200) {
                        this.$emit('word-count-changed');
                    } else {
                        this.errorDialog.active = true;
                    }
                });
            },
            loadChapters() {
                if (!this.$props.bookId) {
                    return;
                }

                const page = this.tableOptions.page || 1;
                const perPage = this.tableOptions.itemsPerPage || 50;

                this.chaptersLoading = true;
                this.chapters = [];

                axios.post('/chapters', {
                    'bookId': this.$props.bookId,
                    'page': page,
                    'perPage': perPage,
                }).then((response) => {
                    for (let chapterIndex = 0; chapterIndex < response.data.chapters.length; chapterIndex++) {
                        response.data.chapters[chapterIndex].wordCountsLoaded = false;
                    }
                    
                    this.book = response.data.book;
                    this.chapters = response.data.chapters;
                    this.totalChapters = response.data.total;
                    this.loadVisibleWordCounts();
                }).catch(() => {
                    this.chaptersLoading = false;
                });
            },
            loadVisibleWordCounts() {
                const chapterIds = this.chapters.map((chapter) => chapter.id);

                if (!chapterIds.length) {
                    this.chaptersLoading = false;
                    return;
                }

                axios.get('/chapters/word-counts/' + this.$props.bookId, {
                    params: {
                        chapterIds: chapterIds,
                    }
                }).then((response) => {
                    this.chapterStatusUpdate(response.data);
                }).finally(() => {
                    this.chaptersLoading = false;
                });
            },
            showStartReviewDialog(bookId, bookName, chapterId, chapterName) {
                this.startReviewDialog.bookName = bookName;
                this.startReviewDialog.bookId = bookId;
                this.startReviewDialog.chapterName = chapterName;
                this.startReviewDialog.chapterId = chapterId;
                this.startReviewDialog.active = true;
            },
            formatNumber: formatNumber
        }
    }
</script>

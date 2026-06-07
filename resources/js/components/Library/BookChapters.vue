<template>
    <v-container fluid class="book-chapters py-0 px-0">
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
        <v-alert
            v-if="chaptersError"
            type="error"
            variant="tonal"
            density="compact"
            class="my-3"
        >
            {{ chaptersError }}
        </v-alert>

        <v-alert
            v-if="chapterStatisticsError"
            type="warning"
            variant="tonal"
            density="compact"
            class="my-3"
        >
            {{ chapterStatisticsError }}
        </v-alert>

        

        <!-- Chapter list -->
        <v-defaults-provider :defaults="tableFooterDefaults">
        <v-data-table-server
            class="book-chapters-table my-4 mb-0 no-hover"
            :headers="[
                { title: 'Chapter', key: 'name'},
                { title: 'Read', key: 'read_count', align: 'center' },
                { title: 'Total', key: 'wordCount.total', align: 'center' },
                { title: 'Unique', key: 'wordCount.unique', align: 'center' },
                { title: 'Known', key: 'wordCount.known', align: 'center' },
                { title: 'Highlighted', key: 'wordCount.highlighted', align: 'center' },
                { title: 'New', key: 'wordCount.new', align: 'center' },
                { title: 'Actions', key: 'actions', sortable: false },
            ]"
            :items="chapters"
            :loading="chaptersLoading"
            v-model:options="tableOptions"
            :items-length="totalChapters"
            :items-per-page-options="itemsPerPageOptions"
        >
            <template v-slot:no-data>
                <span>{{ chaptersError ? 'Unable to load chapters.' : 'No data available' }}</span>
            </template>


            <!-- Read status -->
            <template v-slot:item.read_count="{ item }">
                <v-chip
                    v-if="item.read_count > 0"
                    small
                    class="read-status-chip"
                    variant="outlined"
                    color="primary"
                    title="Chapter has been finished at least once."
                >
                    <v-icon small left>mdi-check</v-icon>
                    {{ item.read_count }}x
                </v-chip>
                <v-chip
                    v-else
                    small
                    class="read-status-chip"
                    variant="outlined"
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
                <template v-if="hasLoadedWordCount(item)">
                    {{ formatNumber(item.wordCount.total) }}
                </template>

                <!-- Skeleton -->
                <template v-else-if="wordCountUnavailable(item)">
                    <span class="text-error">Unavailable</span>
                </template>

                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Unique words -->
            <template v-slot:item.wordCount.unique="{ item }">
                <!-- Count -->
                <template v-if="hasLoadedWordCount(item)">
                    {{ formatNumber(item.wordCount.unique) }}
                </template>

                <!-- Skeleton -->
                <template v-else-if="wordCountUnavailable(item)">
                    <span class="text-error">Unavailable</span>
                </template>

                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Known words -->
            <template v-slot:item.wordCount.known="{ item }">
                <!-- Count -->
                <template v-if="hasLoadedWordCount(item)">
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
                <template v-else-if="wordCountUnavailable(item)">
                    <span class="text-error">Unavailable</span>
                </template>

                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Highlighted words -->
            <template v-slot:item.wordCount.highlighted="{ item }">
                <!-- Count -->
                <template v-if="hasLoadedWordCount(item)">
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
                <template v-else-if="wordCountUnavailable(item)">
                    <span class="text-error">Unavailable</span>
                </template>

                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>


            <!-- New words -->
            <template v-slot:item.wordCount.new="{ item }">
                <!-- Count -->
                <template v-if="hasLoadedWordCount(item)">
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
                <template v-else-if="wordCountUnavailable(item)">
                    <span class="text-error">Unavailable</span>
                </template>

                <v-skeleton-loader
                        v-else
                        class="chapter-word-count-skeleton rounded-pill"
                        type="image"
                ></v-skeleton-loader>
            </template>

            <!-- Actions -->
            <template v-slot:item.actions="{ item }">
                <div class="chapter-actions d-flex justify-center">
                    <!-- Action buttons -->
                    <template v-if="item.processing_status == 'processed'">
                        <v-btn icon density="compact" size="small" :to="'/chapters/read/' + item.id" title="Read"><v-icon>mdi-book-open-variant</v-icon></v-btn>
                        <v-menu rounded location="bottom end" :offset="[0, -5]">
                            <template v-slot:activator="{ props }">
                                <v-btn icon density="compact" size="small" v-bind="props"><v-icon>mdi-dots-horizontal</v-icon></v-btn>
                            </template>
                            <v-list class="pa-0" density="compact" width="100">
                                <v-list-item @click="showEditChapterDialog(item.id)"><v-list-item-title>Edit</v-list-item-title></v-list-item>
                                <v-list-item @click="showStartReviewDialog(book.id, book.name, item.id, item.name)"><v-list-item-title>Review</v-list-item-title></v-list-item>
                                <v-list-item @click="showDeleteChapterDialog(item)"><v-list-item-title>Delete</v-list-item-title></v-list-item>
                            </v-list>
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
        </v-data-table-server>
        </v-defaults-provider>
    </v-container>
</template>

<script>
    import { onScopeDispose } from 'vue';
    import {formatNumber} from './../../helper.js';
    export default {
        data: function() {
            return {
                book: null,
                bookWordCount: null,
                chapters: [],
                chaptersLoading: false,
                chaptersError: '',
                chapterStatisticsError: '',
                chapterRequestSequence: 0,
                tableOptions: {
                    page: 1,
                    itemsPerPage: 50,
                    sortBy: [],
                    sortDesc: [],
                },
                itemsPerPageOptions: [10, 25, 50, { value: -1, title: 'All' }],
                tableFooterDefaults: {
                    VSelect: {
                        menuProps: {
                            location: 'bottom',
                            locationStrategy: this.positionFooterSelectMenu,
                            scrollStrategy: 'reposition',
                        },
                    },
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
            bookId() {
                this.resetTableState();
                this.loadChapters();
            },
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
            positionFooterSelectMenu(data, props, contentStyles) {
                const viewportMargin = 12;

                const updateLocation = () => {
                    const target = Array.isArray(data.target.value) ? null : data.target.value;
                    const content = data.contentEl.value;

                    if (!target || !content) {
                        return;
                    }

                    const targetBox = target.getBoundingClientRect();
                    const contentHeight = content.offsetHeight || content.scrollHeight;
                    const contentWidth = Math.max(content.offsetWidth || targetBox.width, targetBox.width);
                    const spaceBelow = window.innerHeight - targetBox.bottom - viewportMargin;
                    const spaceAbove = targetBox.top - viewportMargin;
                    const openBelow = spaceBelow >= contentHeight || spaceBelow >= spaceAbove;
                    const availableHeight = Math.max(80, openBelow ? spaceBelow : spaceAbove);
                    const unclampedTop = openBelow
                        ? targetBox.bottom
                        : targetBox.top - contentHeight;
                    const maxTop = window.innerHeight - Math.min(contentHeight, availableHeight) - viewportMargin;
                    const top = Math.max(viewportMargin, Math.min(unclampedTop, maxTop));
                    const left = Math.max(
                        viewportMargin,
                        Math.min(targetBox.left, window.innerWidth - contentWidth - viewportMargin)
                    );

                    Object.assign(contentStyles.value, {
                        "--v-overlay-anchor-origin": openBelow ? "bottom left" : "top left",
                        transformOrigin: openBelow ? "left top" : "left bottom",
                        position: "fixed",
                        top: `${Math.round(top)}px`,
                        left: `${Math.round(left)}px`,
                        minWidth: `${Math.round(targetBox.width)}px`,
                        maxWidth: `${Math.max(Math.round(contentWidth), Math.round(targetBox.width))}px`,
                        maxHeight: `${Math.round(availableHeight)}px`,
                    });
                };

                window.addEventListener("resize", updateLocation, { passive: true });
                document.addEventListener("scroll", updateLocation, true);
                requestAnimationFrame(updateLocation);

                onScopeDispose(() => {
                    window.removeEventListener("resize", updateLocation);
                    document.removeEventListener("scroll", updateLocation, true);
                });

                return { updateLocation };
            },
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
            resetTableState() {
                this.chapters = [];
                this.totalChapters = 0;
                this.chaptersError = '';
                this.chapterStatisticsError = '';
                this.tableOptions = {
                    ...this.tableOptions,
                    page: 1,
                };
            },
            loadChapters() {
                if (!this.$props.bookId) {
                    return;
                }

                const requestSequence = ++this.chapterRequestSequence;
                const page = this.tableOptions.page || 1;
                const itemsPerPage = Number(this.tableOptions.itemsPerPage || 50);
                const requestData = {
                    bookId: this.$props.bookId,
                    page: page,
                };

                if (itemsPerPage === -1) {
                    requestData.all = true;
                } else {
                    requestData.perPage = itemsPerPage;
                }

                this.chaptersLoading = true;
                this.chaptersError = '';
                this.chapterStatisticsError = '';

                axios.post('/chapters', requestData).then((response) => {
                    if (requestSequence !== this.chapterRequestSequence) {
                        return;
                    }

                    const responseChapters = Array.isArray(response.data.chapters) ? response.data.chapters : [];

                    responseChapters.forEach((chapter) => {
                        if (chapter.wordCountsLoaded === undefined) {
                            chapter.wordCountsLoaded = chapter.processing_status === 'processed' && !!chapter.wordCount;
                        }
                    });
                    
                    this.book = response.data.book;
                    this.chapters = responseChapters;
                    this.totalChapters = Number(response.data.total || 0);
                    this.chaptersLoading = false;
                }).catch((error) => {
                    if (requestSequence !== this.chapterRequestSequence) {
                        return;
                    }

                    this.chaptersLoading = false;
                    this.chaptersError = this.chapterRequestErrorMessage(error);

                    console.error('Failed to load book chapters.', {
                        bookId: this.$props.bookId,
                        page: page,
                        itemsPerPage: itemsPerPage,
                        status: error.response?.status,
                        errors: error.response?.data?.errors,
                    });
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
            chapterRequestErrorMessage(error) {
                if (error.response?.status === 422) {
                    return 'The chapter request was rejected. Check the page size and try again.';
                }

                return 'Unable to load chapters right now. The previous chapter data is still shown.';
            },
            hasLoadedWordCount(chapter) {
                return chapter.processing_status === 'processed' && chapter.wordCountsLoaded && !!chapter.wordCount;
            },
            wordCountUnavailable(chapter) {
                return chapter.processing_status === 'processed' && !this.chaptersLoading && (!chapter.wordCountsLoaded || !chapter.wordCount);
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

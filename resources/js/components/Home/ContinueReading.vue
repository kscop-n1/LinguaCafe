<template>
    <div
        v-if="filteredBookmarks.length"
        ref="bookmarks"
    >
        <delete-bookmark-dialog 
            v-model="deleteBookmarkDialogPopup.show" 
            @confirm="deleteBookmarkDialog"
        />
        <div class="subheader subheader-margin-top d-flex justify-space-between">
            <div>Continue reading</div>
            <div class="body-2" v-if="lastPage > 1">
                {{ currentPage }} / {{ lastPage }}
                <v-btn 
                    class="ml-2"
                    icon
                    @click="previousPage"
                >
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <v-btn 
                    icon 
                    @click="nextPage"
                >
                    <v-icon>mdi-arrow-right</v-icon>
                </v-btn>
            </div>
        </div>
        <div id="bookmarks">
            <v-card
                outlined
                class="bookmark relative rounded-lg mr-4 pa-2 pt-6"
                v-for="bookmark in filteredBookmarks" 
                :to="'/chapters/read/' + bookmark.chapter.id"
            >
                    <v-btn 
                        icon 
                        class="delete-button"
                        @click.prevent.stop="showDeleteBookmarkDialog(bookmark.id)"
                    >
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
                <img
                    v-if="bookmark.book.cover_image"
                    class="cover-image rounded-lg ma-2"
                    :src="'/images/book_images/' + bookmark.book.cover_image"
                ></img>
                <div v-else class="cover-image d-flex align-items-center mx-auto my-2">
                    <NoBookCoverIcon class="px-1" />
                </div>
                <div class="book-title">{{ bookmark.book.name }}</div>
                <div class="chapter-title">{{ bookmark.chapter.name }}</div>
            </v-card>
        </div>
    </div>
</template>

<script>
    export default {
        data: function() {
            return {
                bookmarks: [],
                filteredBookmarks: [],
                currentPage: 1,
                defaultPageSize: 4,
                currentPageSize: 4,
                deleteBookmarkDialogPopup: {
                    show: false,
                    bookmarkId: null,
                },
            }
        },
        computed: {
            lastPage() {
                return Math.ceil(this.bookmarks.length / this.currentPageSize)
            }
        },
        mounted() {
            this.loadBookmarks()

            window.addEventListener('resize', this.resizeHandle);
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.resizeHandle);
        },
        methods: {
            showDeleteBookmarkDialog(bookmarkId)  {
                this.deleteBookmarkDialogPopup.show = true
                this.deleteBookmarkDialogPopup.bookmarkId = bookmarkId
            },
            deleteBookmarkDialog() {
                axios.delete(`/bookmarks/${this.deleteBookmarkDialogPopup.bookmarkId}`).then((response) => {
                    this.deleteBookmarkDialogPopup.show = false
                    this.loadBookmarks()
                }).catch((error) => {
                    this.deleteBookmarkDialogPopup.show = false
                })
            },  
            loadBookmarks() {
                this.currentPage = 1
                axios.get('/bookmarks/next-chapter').then((response) => {
                    this.bookmarks = response.data.data.filter((bookmark) => {
                        if (!bookmark.chapter) {
                            return false;
                        }

                        return true;
                    });

                    this.filterBookmarks()
                    this.$nextTick(this.resizeHandle)
                })
            },
            nextPage() {
                if (this.currentPage * this.currentPageSize >= this.bookmarks.length) {
                    return
                }
                
                this.currentPage ++
                this.filterBookmarks()
            },
            previousPage() {
                if (this.currentPage === 1) {
                    return
                }
                
                this.currentPage --
                this.filterBookmarks()
            },
            filterBookmarks() {
                if (!this.bookmarks.length) {
                    return
                }

                let pageStart = (this.currentPage - 1) * this.currentPageSize;
                let pageEnd = this.currentPage * this.currentPageSize - 1;
                this.filteredBookmarks = this.bookmarks.filter((bookmark, index) => {
                    if (index < pageStart || index > pageEnd) {
                        return false;
                    }

                    return true;
                });
            },
            resizeHandle() {
                let previousPageSize = this.currentPageSize

                this.currentPageSize = this.defaultPageSize
                if (this.$refs.bookmarks.clientWidth < 930) {
                    this.currentPageSize = 3
                }

                if (this.$refs.bookmarks.clientWidth < 600) {
                    this.currentPageSize = 2
                }

                if (this.$refs.bookmarks.clientWidth < 450) {
                    this.currentPageSize = 1
                }

                if (previousPageSize !== this.currentPageSize) {
                    this.currentPage = 1
                }

                this.filterBookmarks()
            },
        }
    }
</script>

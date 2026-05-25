<template>
    <v-container id="books" :class="{'book-opened': openedBook !== -1}">
        <!-- Error dialog -->
        <error-dialog
            v-if="errorDialog.active"
            v-model="errorDialog.active"
            content="An error has occurred while deleting the book."
        ></error-dialog>

        <!-- Review dialog -->
        <start-review-dialog
            v-model="startReviewDialog.visible"
            :book-id="startReviewDialog.bookId"
            :book-name="startReviewDialog.bookName"
        ></start-review-dialog>

        <!-- Import dialog -->
        <import-dialog
            v-if="importDialog.active"
            v-model="importDialog.active"
            :language="$props.language"
            @import-finished="importFinished"
        ></import-dialog>

        <!-- Edit or add book dialog -->
        <edit-book-dialog
            v-if="editBookDialog.active"
            v-model="editBookDialog.active"
            :book-id="editBookDialog.bookId"
            :book-name="editBookDialog.bookName"
            :book-cover="editBookDialog.bookCover"
            @book-saved="loadBooks"
        ></edit-book-dialog>

        <!-- Delete book dialog -->
        <delete-book-dialog
            v-if="deleteBookDialog.active"
            v-model="deleteBookDialog.active"
            :book-id="deleteBookDialog.bookId"
            :book-name="deleteBookDialog.bookName"
            @confirm="deleteBook"
        ></delete-book-dialog>

        <!-- Toolbar -->
        <div id="toolbar" class="d-flex mx-auto mt-6 mb-2">
              <v-menu location="bottom" :offset="8" class="rounded-lg">
                    <template v-slot:activator="{ props }">
                        <v-btn color="foreground" rounded variant="flat" v-bind="props">
                            Layout
                            <v-icon v-if="props?.['aria-expanded'] === 'true'">mdi-chevron-up</v-icon>
                            <v-icon v-if="props?.['aria-expanded'] !== 'true'">mdi-chevron-down</v-icon>
                        </v-btn>
                    </template>
                    <v-list class="pa-0" density="compact">
                        <v-list-item prepend-icon="mdi-view-list" @click="setLayout('table')">
                            <v-list-item-title>List</v-list-item-title>
                        </v-list-item>
                        <v-list-item prepend-icon="mdi-view-module" @click="setLayout('cover-only')">
                            <v-list-item-title>Cover only</v-list-item-title>
                        </v-list-item>
                        <v-list-item prepend-icon="mdi-view-agenda" @click="setLayout('detailed')">
                            <v-list-item-title>Detailed</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>

                <v-spacer></v-spacer>
                <v-menu location="bottom" :offset="8" class="rounded-lg">
                    <template v-slot:activator="{ props }">
                        <v-btn class="library-small-screen" rounded variant="flat" v-bind="props">
                            Library
                            <v-icon v-if="props?.['aria-expanded'] === 'true'">mdi-chevron-up</v-icon>
                            <v-icon v-if="props?.['aria-expanded'] !== 'true'">mdi-chevron-down</v-icon>
                        </v-btn>
                    </template>
                    <v-list class="pa-0" density="compact">
                        <v-list-item prepend-icon="mdi-book-plus" @click="showEditBookDialog(null)">
                            <v-list-item-title>Create book</v-list-item-title>
                        </v-list-item>
                        <v-list-item prepend-icon="mdi-import" @click="importDialog.active = true;">
                            <v-list-item-title>Import</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>

                <v-btn
                    rounded
                    class="library-large-screen mx-0"
                    color="primary"
                    @click="showEditBookDialog(null)"
                >
                    <v-icon class="mr-1">mdi-book-plus</v-icon>Create book
                </v-btn>
                <v-btn
                    rounded
                    class="library-large-screen ml-2"
                    color="primary"
                    @click="importDialog.active = true;"
                >
                    <v-icon class="mr-1">mdi-import</v-icon>Import
                </v-btn>
        </div>

        <!-- Book list table -->
        <book-list-table
            v-if="openedBook === -1 && layout === 'table'"
            :books="books"
            @show-edit-book-dialog="showEditBookDialog"
            @show-delete-book-dialog="showDeleteBookDialog"
            @show-start-review-dialog="showStartReviewDialog"
            @open-book="openBook"
        />

        <!-- Book list detailed -->
        <book-list-detailed
            v-if="openedBook === -1 && layout === 'detailed'"
            :books="books"
            @show-edit-book-dialog="showEditBookDialog"
            @show-delete-book-dialog="showDeleteBookDialog"
            @show-start-review-dialog="showStartReviewDialog"
            @open-book="openBook"
        />

        <!-- Book list cover only -->
        <book-list-cover-only
            v-if="openedBook === -1 && layout === 'cover-only'"
            :books="books"
            @open-book="openBook"
        />

        <book
            v-if="openedBook !== -1"
            :book="books[openedBook]"
            @show-edit-book-dialog="showEditBookDialog"
            @show-delete-book-dialog="showDeleteBookDialog"
            @show-start-review-dialog="showStartReviewDialog"
            @close-book="closeBook"
        />
    </v-container>
</template>

<script>
    import {formatNumber} from './../../helper.js';
    import { DefaultLocalStorageManager } from './../../services/LocalStorageManagerService';
    export default {
        data: function() {
            return {
                layout: DefaultLocalStorageManager.loadSetting('library-layout') || 'table',
                theme: DefaultLocalStorageManager.loadSetting('theme') || 'light',
                books: [],
                openedBook: -1,
                errorDialog: {
                    active: false,
                },
                importDialog: {
                    active: false,
                },
                editBookDialog: {
                    active: false,
                    bookId: -1
                },
                deleteBookDialog: {
                    active: false,
                    bookId: -1,
                    bookName: '',
                },
                startReviewDialog: {
                    visible: false,
                    bookId: -1,
                    bookName: '',
                }
            }
        },
        props: {
            language: String
        },
        mounted() {
            this.loadBooks();
        },
        methods: {
            loadBookWordCounts(index) {
                this.books[index].wordCountLoading = true;
                this.books[index].wordCount = null;

                axios.get('/books/get-word-counts/' + this.books[index].id).then((response) => {
                    if (response.data !== 'error') {
                        this.books[index].wordCountLoading = false;
                        this.books[index].wordCount = response.data;
                    }
                });
            },
            showEditBookDialog(book = null) {
                this.editBookDialog.active = true;
                if (book === null) {
                    this.editBookDialog.bookId = -1;
                    this.editBookDialog.bookCover = null;
                    this.editBookDialog.bookName = '';
                } else {
                    this.editBookDialog.bookId = book.id;
                    this.editBookDialog.bookCover = book.cover_image;
                    this.editBookDialog.bookName = book.name;
                }
            },
            showDeleteBookDialog(book) {
                this.deleteBookDialog.active = true;
                this.deleteBookDialog.bookId = book.id;
                this.deleteBookDialog.bookName = book.name;
            },
            deleteBook() {
                axios.post('/books/delete', {
                    'bookId': this.deleteBookDialog.bookId,
                }).catch((e) => {
                    this.errorDialog.active = true;
                }).then((response) => {
                    if (response.status === 200) {
                        this.loadBooks();
                    } else {
                        this.errorDialog.active = true;
                    }
                });
            },
            openBook(bookId) {
                var bookIndex = -1;
                for (let i = 0; i < this.books.length; i++) {
                    if (this.books[i].id === bookId) {
                        bookIndex = i;
                        break;
                    }
                }

                if (bookIndex === -1) {
                    this.openedBook = -1;
                    if (this.$route.path !== '/books') {
                        this.$router.replace('/books');
                    }
                    return;
                }

                this.openedBook = bookIndex;

                // update url
                if (this.$route.path !== ('/books/' + this.books[bookIndex].id)) {
                    this.$router.push('/books/' + this.books[bookIndex].id);
                }
            },
            closeBook() {
                this.openedBook = -1;

                // update url
                if (this.$route.path !== ('/books')) {
                    this.$router.push('/books');
                }
            },
            showStartReviewDialog(bookId, bookName) {
                this.startReviewDialog.bookName = bookName;
                this.startReviewDialog.bookId = bookId;
                this.startReviewDialog.visible = true;
            },
            importFinished() {
                this.loadBooks();
            },
            loadBooks() {
                axios.post('/books').then((response) => {
                    this.openedBook = -1;
                    for (let bookIndex = 0; bookIndex < response.data.length; bookIndex ++) {
                        response.data[bookIndex].chaptersVisible = false;
                        response.data[bookIndex].wordCountLoading = false;
                    }

                    this.books = response.data;

                    // open book from url param
                    if (this.$route.params.bookId !== undefined) {
                        this.$nextTick(() => {
                            this.openBook(parseInt(this.$route.params.bookId));
                        });
                    }
                });
            },
            setLayout(newLayout) {
                this.layout = newLayout;
                DefaultLocalStorageManager.saveSetting('library-layout', newLayout);
            },
            formatNumber: formatNumber
        }
    }
</script>

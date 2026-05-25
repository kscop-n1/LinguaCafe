<template>
    <!-- Book list detailed -->
    <div id="book-list" class="detailed">
        <v-card
            variant="outlined"
            :id="'book-' + book.id"
            class="book detailed rounded-lg mx-auto my-6"
            v-for="(book, index) in books"
            :key="index"
        >
            <div class="book-box">
                <!-- Cover image -->
                <div class="cover-image-box rounded-lg">
                    <img
                        v-if="book.cover_image"
                        class="cover-image rounded-lg"
                        :src="'/images/book_images/' + book.cover_image"
                    ></img>
                    <NoBookCoverIcon v-else/>
                </div>

                <!-- Title bar -->
                <v-card-text class="book-information pa-0 pl-3">
                    <v-card-title class="book-title pa-3">
                        <div class="book-title-text default-font">{{ book.name }}</div>
                        <v-spacer></v-spacer>
                        <v-menu content-class="book-menu" rounded location="bottom end" :offset="[0, -5]">
                            <template v-slot:activator="{ props }">
                                <v-btn icon v-bind="props"><v-icon>mdi-dots-horizontal</v-icon></v-btn>
                            </template>
                            <v-list class="pa-0" density="compact">
                                <v-list-item @click="loadBookWordCounts(index)"><v-list-item-title>Load word counts</v-list-item-title></v-list-item>
                                <v-list-item @click="showEditBookDialog(book)"><v-list-item-title>Edit</v-list-item-title></v-list-item>
                                <v-list-item @click="showStartReviewDialog(book)"><v-list-item-title>Review</v-list-item-title></v-list-item>
                                <v-list-item @click="showDeleteBookDialog(book)"><v-list-item-title>Delete</v-list-item-title></v-list-item>
                            </v-list>
                        </v-menu>
                    </v-card-title>

                    <!-- Word counts loading animation -->
                    <div class="book-info-not-loaded-box mb-1" v-if="book.wordCount === null">
                        <template v-if="book.wordCountLoading">
                            <v-progress-circular indeterminate color="primary" />
                        </template>
                    </div>

                    <!-- Word counts -->
                    <v-table density="compact" class="book-info-table no-hover pb-4 mx-auto" v-if="book.wordCount !== null">
                        <tbody>
                            <tr>
                                <td width="200px">Total words</td>
                                <td class="text-center"><div class="info-table-value">{{ formatNumber(book.wordCount.total) }}</div></td>
                            </tr>
                            <tr>
                                <td width="200px">Unique words</td>
                                <td class="text-center"><div class="info-table-value">{{ formatNumber(book.wordCount.unique) }}</div></td>
                            </tr>
                            <tr>
                                <td width="200px">Known words</td>
                                <td class="text-center"><div class="info-table-value">{{ formatNumber(book.wordCount.known) }}</div></td>
                            </tr>
                            <tr>
                                <td width="200px">Highlighted words</td>
                                <td class="text-center"><div class="info-table-value highlighted-words px-2 rounded-xl">{{ formatNumber(book.wordCount.highlighted) }}</div></td>
                            </tr>
                            <tr>
                                <td width="200px">New words</td>
                                <td class="text-center"><div class="info-table-value new-words px-2 rounded-xl">{{ formatNumber(book.wordCount.new) }}</div></td>
                            </tr>
                        </tbody>
                    </v-table>
                <v-card-actions>
                    <v-spacer />
                    <v-btn rounded class="mx-0" color="primary" @click="openBook(book.id)" v-if="!book.chaptersVisible">Open</v-btn>
                    <v-btn rounded class="mx-0" color="primary"  v-if="book.chaptersVisible" @click="addChapter(book.id)"><v-icon> mdi-plus</v-icon>Add chapter</v-btn>
                </v-card-actions>
                </v-card-text>
            </div>
        </v-card>
    </div>
</template>

<script>
    import {formatNumber} from './../../../helper.js';
    export default {
        data: function() {
            return {
            }
        },
        props: {
            books: Array
        },
        mounted() {
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
            showEditBookDialog(book) {
                this.$emit('show-edit-book-dialog', book);
            },
            showDeleteBookDialog(book) {
                this.$emit('show-delete-book-dialog', book);
            },
            openBook(bookId) {
                this.$emit('open-book', bookId);
            },
            showStartReviewDialog(book) {
                this.$emit('show-start-review-dialog', book.id, book.name);
            },
            formatNumber: formatNumber
        }
    }
</script>

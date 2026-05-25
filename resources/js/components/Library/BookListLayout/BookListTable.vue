<template>
    <div id="book-list" class="table-layout">
        <!-- Book list detailed -->
        <v-card variant="outlined" class="border rounded-lg mt-4">
            <v-card-title>
                <v-text-field
                    v-model="booksTextFilter"
                    append-icon="mdi-magnify"
                    label="Search"
                    variant="filled"
                    density="compact"
                    hide-details
                    single-line
                    rounded
                ></v-text-field>
            </v-card-title>

            <v-data-table
                class="ma-4 mb-0 no-hover"
                :headers="[
                    {
                        title: 'Cover',
                        key: 'cover_image',
                        align: 'center',
                        width: '140px',
                        sortable: false,
                    },
                    {
                        title: 'Title',
                        key: 'name',
                        align: 'left',
                    },
                    {
                        title: 'Length',
                        key: 'word_count',
                        align: 'center',
                        width: '140px',
                    },
                    {
                        title: 'Actions',
                        key: 'actions',
                        align: 'center',
                        width: '140px',
                        sortable: false,
                    },
                ]"
                :items="books"
                :search="booksTextFilter"
            >
                <!-- Cover image -->
                <template v-slot:item.cover_image="{ item }">
                    <img
                        v-if="item.cover_image"
                        class="cover-image rounded-lg ma-2"
                        :src="'/images/book_images/' + item.cover_image"
                    ></img>
                    <div v-else class="cover-image d-flex align-items-center mx-auto my-2">
                        <NoBookCoverIcon class="px-1" />
                    </div>
                </template>
                
                <!-- Length -->
                <template v-slot:item.word_count="{ item }">
                    {{ formatNumber(item.word_count) }}
                </template>
                
                <!-- Actions -->
                <template v-slot:item.actions="{ item }">
                    <v-btn icon title="Open book" @click="openBook(item.id)"><v-icon>mdi-book-open</v-icon></v-btn>
                    <v-menu content-class="book-menu" rounded location="bottom end" :offset="[0, -5]">
                        <template v-slot:activator="{ props }">
                            <v-btn icon v-bind="props"  title="Actions">
                                <v-icon>mdi-dots-horizontal</v-icon>
                            </v-btn>
                        </template>
                        <v-list class="pa-0" density="compact">
                            <v-list-item @click="showEditBookDialog(item)"><v-list-item-title>Edit</v-list-item-title></v-list-item>
                            <v-list-item @click="showStartReviewDialog(item)"><v-list-item-title>Review</v-list-item-title></v-list-item>
                            <v-list-item @click="showDeleteBookDialog(item)"><v-list-item-title>Delete</v-list-item-title></v-list-item>
                        </v-list>
                    </v-menu>
                </template>

            </v-data-table>
        </v-card>
    </div>
</template>

<script>
    import {formatNumber} from './../../../helper.js';
    export default {
        data: function() {
            return {
                booksTextFilter: '',
            }
        },
        props: {
            books: Array
        },
        mounted() {
        },
        methods: {
            openBook(bookId) {
                this.$emit('open-book', bookId);
            },
            showEditBookDialog(book) {
                this.$emit('show-edit-book-dialog', book);
            },
            showDeleteBookDialog(book) {
                this.$emit('show-delete-book-dialog', book);
            },
            showStartReviewDialog(book) {
                this.$emit('show-start-review-dialog', book.id, book.name);
            },
            formatNumber: formatNumber
        }
    }
</script>

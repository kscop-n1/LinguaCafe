<template>
    <div v-if="bookmarks.length">
        <div class="subheader subheader-margin-top">Continue reading</div>
        <div id="bookmarks">
            <v-card
                outlined
                class="bookmark rounded-lg mr-4 pa-2"
                v-for="bookmark in bookmarks" 
                :to="'/chapters/read/' + bookmark.chapter.id"
            >
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
            }
        },
        props: {
        },
        mounted() {
            this.loadBookmarks()
        },
        methods: {
            loadBookmarks() {
                axios.get('/bookmarks/next-chapter').then((response) => {
                    this.bookmarks = response.data.data
                })
            }
        }
    }
</script>

<script setup lang="ts">
import updateNotes from '@config/UpdateNotes'
</script>

<template>
    <ContentSpacer class="w-full">
        <PageSectionTitle title="Update history" />

        <UChangelogVersions>
            <UChangelogVersion
                v-for="(updateNote, updateNoteIndex) in updateNotes"
                :key="updateNoteIndex"
                :title="updateNote.title"
                :description="updateNote.description"
                :date="updateNote.date"
                :to="`/updates/${updateNote.title}`"
            />
        </UChangelogVersions>
    </ContentSpacer>
</template>

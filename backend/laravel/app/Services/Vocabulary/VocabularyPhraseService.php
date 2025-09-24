<?php

namespace App\Services\Vocabulary;

use App\Enums\ChapterProcessingStatusEnum;
use App\Helpers\Language\LanguageConfig;
use App\Models\Chapter;
use App\Models\ExampleSentence;
use App\Models\Phrase;
use App\Models\User;
use App\Services\TextBlockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VocabularyPhraseService
{
    public function createPhrase(User $user, LanguageConfig $language, array $words, int $stage, string $reading, string $translation)
    {
        $phrase = new Phrase();
        $phrase->user_id = $user->id;
        $phrase->language = $language->name;
        $phrase->stage = $stage;
        $phrase->reading = $reading;
        $phrase->translation = $translation;
        $phrase->words = json_encode($words);

        if (!is_array($words)) {
            throw new \Exception('Words parameter must be an array!');
        }

        if (!count($words)) {
            throw new \Exception('Words parameter must not be empty!');
        }

        $wordSeparator = $language->hasSpaces() ? ' ' : '';
        $phrase->words_searchable = implode($wordSeparator, $words);

        $phrase->save();

        // TODO: move update phrase ids code to separate function
        // update phrase ids in chapter texts
        $chapterIds = Chapter::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value)
            ->pluck('id')
            ->toArray();

        $phraseWords = array_unique(json_decode($phrase->words));
        foreach ($chapterIds as $chapterId) {
            DB::transaction(function () use ($chapterId, $phraseWords, $user, $language, $phrase) {
                $chapter = Chapter::lockForUpdate()
                    ->where('id', $chapterId)
                    ->where('user_id', $user->id)
                    ->where('language', $language->name)
                    ->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value)
                    ->first();

                $uniqueWords = json_decode($chapter->unique_words);

                if (count(array_intersect($uniqueWords, $phraseWords)) === count($phraseWords)) {
                    $words = $chapter->getProcessedText();

                    $textBlock = new TextBlockService($user->id, $language->name);
                    $textBlock->setProcessedWords($words);
                    $textBlock->collectUniqueWords();
                    $phraseIdsChanged = $textBlock->updatePhraseIds($phrase);

                    // save chapter words
                    if ($phraseIdsChanged) {
                        $chapter->setProcessedText($textBlock->processedWords);
                        $chapter->save();
                    }
                }
            });
        }

        // update phrase ids in example sentences
        $exampleSentences = ExampleSentence::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->get();

        DB::beginTransaction();
        foreach ($exampleSentences as $exampleSentence) {
            $uniqueWords = json_decode($exampleSentence->unique_words);
            if (count(array_intersect($uniqueWords, $phraseWords)) !== count($phraseWords)) {
                continue;
            }

            $textBlock = new TextBlockService($user->id, $language->name);
            $textBlock->setProcessedWords(json_decode($exampleSentence->words));
            $textBlock->collectUniqueWords();
            $textBlock->updatePhraseIds($phrase);
            $textBlock->createNewEncounteredWords();

            $exampleSentence->words = json_encode($textBlock->processedWords);
            $exampleSentence->unique_words = json_encode($textBlock->uniqueWords);
            $exampleSentence->save();
        }

        DB::commit();

        return $phrase->id;
    }

    public function indexPhraseInChapter($chapterId, $userId, $language, $phrase)
    {
        DB::transaction(function () use ($chapterId, $userId, $language, $phrase) {
            $phraseWords = json_decode($phrase->words);

            $chapter = Chapter::lockForUpdate()
                ->where('id', $chapterId)
                ->where('user_id', $userId)
                ->where('language', $language)
                ->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value)
                ->first();

            if (!$chapter) {
                throw new \Exception('Chapter not found.');
            }

            $uniqueWords = json_decode($chapter->unique_words);

            if (count(array_intersect($uniqueWords, $phraseWords)) === count($phraseWords)) {
                $words = $chapter->getProcessedText();

                $textBlock = new TextBlockService($userId, $language);
                $textBlock->setProcessedWords($words);
                $textBlock->collectUniqueWords();
                $phraseIdsChanged = $textBlock->updatePhraseIds($phrase);

                // save chapter words
                if ($phraseIdsChanged) {
                    $chapter->setProcessedText($textBlock->processedWords);
                    $chapter->save();
                }
            }
        });
    }

    public function updatePhrase(User $user, Phrase $phrase, Collection $phraseData, ?int $stage): void
    {

        if ($user->id !== $phrase->user_id) {
            throw new \Exception('User has no permission to update this word.');
        }

        if ($stage !== null) {
            $phrase->setStage($stage);
        }

        // TODO: make phrase fields nullable. this transform is required
        // because of improper DB schema
        $phraseData->transform(function ($attribute) {
            if ($attribute === null) {
                $attribute = '';
            }

            return $attribute;
        });

        $phrase->update($phraseData->toArray());
        $phrase->save();
    }

    public function deletePhrase(User $user, Phrase $phrase)
    {
        if ($user->id !== $phrase->user_id) {
            throw new \Exception('User has no permission to delete this phrase.');
        }

        // remove phrase ids from text words
        $chapters = Chapter::query()
            ->where('user_id', $user->id)
            ->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value)
            ->where('language', $phrase->language)
            ->get();

        foreach ($chapters as $chapter) {
            $words = $chapter->getProcessedText();
            $chapterChanged = false;

            // delete phrase id from chapter words
            foreach ($words as $word) {
                $index = array_search($phrase->id, $word->phrase_ids);
                if ($index !== false) {
                    $modifiedPhraseIds = $word->phrase_ids;
                    array_splice($modifiedPhraseIds, $index, 1);
                    $word->phrase_ids = $modifiedPhraseIds;
                    $chapterChanged = true;
                }
            }

            // save chapter if changed
            if ($chapterChanged) {
                $chapter->setProcessedText($words);
                $chapter->save();
            }
        }

        // remove phrase ids from example sentence words
        $exampleSentences = ExampleSentence::query()
            ->where('user_id', $user->id)
            ->where('language', $phrase->language)
            ->get();

        DB::beginTransaction();
        foreach ($exampleSentences as $exampleSentence) {
            $exampleSentence->deletePhraseId($phrase->id);
        }

        DB::commit();

        ExampleSentence::query()
            ->where('user_id', $user->id)
            ->where('target_type', 'phrase')
            ->where('target_id', $phrase->id)
            ->delete();

        Phrase::query()
            ->where('user_id', $user->id)
            ->where('language', $phrase->language)
            ->where('id', $phrase->id)
            ->delete();
    }
}

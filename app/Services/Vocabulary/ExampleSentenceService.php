<?php

namespace App\Services\Vocabulary;

use App\DataTransferObjects\InteractiveText\InteractiveTextData;
use App\Helpers\Language\LanguageConfig;
use App\Models\EncounteredWord;
use App\Models\ExampleSentence;
use App\Models\Phrase;
use App\Models\User;
use App\Services\TextBlockService;

class ExampleSentenceService
{
    public function getExampleSentence(User $user, EncounteredWord|Phrase $model): ?InteractiveTextData
    {
        $targetType = $model instanceof Phrase ? 'phrase' : 'word';

        // TODO: ExampleSentence target_type should be a laravel polymorphic relationship instead of this custom solution
        $exampleSentence = ExampleSentence::query()
            ->where('user_id', $user->id)
            ->where('target_type', $targetType)
            ->where('target_id', $model->id)
            ->first();

        if (!$exampleSentence) {
            return null;
        }

        $textBlock = new TextBlockService($user->id, $exampleSentence->language);
        $textBlock->setProcessedWords(json_decode($exampleSentence->words));
        $textBlock->uniqueWords = json_decode($exampleSentence->unique_words);
        $textBlock->prepareTextForReader();
        $textBlock->indexPhrases();

        return $textBlock->getReaderData();
    }

    public function createOrUpdateExampleSentence(
        User $user,
        LanguageConfig $language,
        string $targetType,
        int $targetId,
        array $exampleSentenceWords
    ): void {
        $exampleSentence = ExampleSentence::query()
            ->where('user_id', $user->id)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->first();

        if (!$exampleSentence) {
            $exampleSentence = new ExampleSentence();
            $exampleSentence->user_id = $user->id;
            $exampleSentence->language = $language->name;
            $exampleSentence->target_type = $targetType;
            $exampleSentence->target_id = $targetId;
            $exampleSentence->unique_words = [];
        }

        $textBlock = new TextBlockService($user->id, $language->name);
        $textBlock->setProcessedWords($exampleSentenceWords);
        $textBlock->collectUniqueWords();
        $textBlock->updateAllPhraseIds();

        $exampleSentence->words = json_encode($textBlock->processedWords);
        $exampleSentence->unique_words = json_encode($textBlock->uniqueWords);
        $exampleSentence->save();
    }
}

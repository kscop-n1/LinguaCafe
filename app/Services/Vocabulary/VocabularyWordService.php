<?php

namespace App\Services\Vocabulary;

use App\Models\EncounteredWord;
use App\Models\User;
use Illuminate\Support\Collection;

class VocabularyWordService
{
    public function updateWord(User $user, EncounteredWord $word, Collection $wordData, ?int $stage): void
    {
        if ($word->user_id !== $user->id) {
            throw new \Exception('User has no permission to update this word.');
        }

        if ($stage !== null) {
            $word->setStage($stage);
            $word->save();
        }

        // TODO: make encounteredWord fields nullable. this transform is required
        // because of improper DB schema
        $wordData->transform(function ($attribute) {
            if ($attribute === null) {
                $attribute = '';
            }

            return $attribute;
        });

        $word->update($wordData->toArray());
    }
}

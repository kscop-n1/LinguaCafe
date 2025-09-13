<?php

namespace App\Services;

use App\DataTransferObjects\InteractiveText\InteractiveTextData;
use App\DataTransferObjects\Vocabulary\CsvImportResultData;
use App\DataTransferObjects\Vocabulary\KanjiData;
use App\DataTransferObjects\Vocabulary\KanjiSearchResultData;
use App\DataTransferObjects\Vocabulary\VocabularySearchResultData;
use App\Enums\ChapterProcessingStatusEnum;
use App\Helpers\Language\LanguageConfig;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\ExampleSentence;
use App\Models\Kanji;
use App\Models\Phrase;
use App\Models\Radical;
use App\Models\User;
use App\Queries\VocabularySearchQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Writer;

class VocabularyService
{
    private $itemsPerPage;

    public function __construct()
    {
        $this->itemsPerPage = 30;
    }

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

    public function searchVocabulary(
        User $user,
        LanguageConfig $language,
        string $text,
        int $bookId,
        int $chapterId,
        int $stage,
        string $phrases,
        string $orderBy,
        string $translation,
        int $page
    ): VocabularySearchResultData {
        // get books and chapters
        $books = Book::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->with('chapters', function ($query) {
                $query->select(['id', 'name', 'book_id']);
                $query->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value);
            })
            ->get();

        $search = (new VocabularySearchQuery())->retrieve(
            $user->id,
            $language->name,
            $text,
            $bookId,
            $chapterId,
            $stage,
            $phrases,
            $orderBy,
            $translation
        );

        $data = new \stdClass();
        $data->wordCount = $search->count();
        $data->words = $search->skip(($page - 1) * $this->itemsPerPage)->take($this->itemsPerPage)->get();
        $data->books = $books;
        $data->pageCount = ceil($data->wordCount / $this->itemsPerPage);
        $data->currentPage = $page;
        $data->languageSpaces = $language->hasSpaces();

        return new VocabularySearchResultData(
            wordCount: $data->wordCount = $search->count(),
            words: $search->skip(($page - 1) * $this->itemsPerPage)->take($this->itemsPerPage)->get(),
            books: $books,
            pageCount: ceil($data->wordCount / $this->itemsPerPage),
            currentPage: $page,
            languageSpaces: $language->hasSpaces(),
        );
    }

    public function exportToCsv(
        User $user,
        LanguageConfig $language,
        $text,
        $bookId,
        $chapterId,
        $stage,
        $phrases,
        $orderBy,
        $translation,
        $fields
    ): Writer {
        $words = (new VocabularySearchQuery())->retrieve(
            $user->id,
            $language->name,
            $text,
            $bookId,
            $chapterId,
            $stage,
            $phrases,
            $orderBy,
            $translation
        )->get();

        $csv = Writer::createFromFileObject(new \SplTempFileObject());
        $csv->setDelimiter('|');

        // headers
        $csvArray = [];
        foreach ($fields as $field) {
            if ($field['export']) {
                $csvArray[] = str_replace('Stage', 'Level', $field['headerName']);
            }
        }

        $csv->insertOne($csvArray);

        // insert data to csv
        $phraseWordDelimiter = $language->hasSpaces() ? ' ' : '';
        foreach ($words as $word) {
            $csvArray = [];
            foreach ($fields as $field) {
                if (!$field['export']) {
                    continue;
                }

                $searchObjectProperty = $field['searchObjectProperty'];

                if ($word->type === 'phrase' && $searchObjectProperty === 'word') {
                    $csvArray[] = implode($phraseWordDelimiter, json_decode($word->$searchObjectProperty));
                } else {
                    $csvArray[] = $word->$searchObjectProperty;
                }
            }

            $csv->insertOne($csvArray);
        }

        return $csv;
    }

    public function importFromCsv(
        User $user,
        LanguageConfig $language,
        string $fileName,
        string $delimiter,
        bool $onlyUpdate,
        bool $skipHeader
    ): CsvImportResultData {
        $stageMapping = [
            'new' => 2,
            'ignored' => 1,
            'learned' => 0,
            '1' => -1,
            '2' => -2,
            '3' => -3,
            '4' => -4,
            '5' => -5,
            '6' => -6,
            '7' => -7,
        ];

        DB::disableQueryLog();
        $reader = Reader::createFromPath(storage_path('app/temp') . '/' . $fileName, 'r');
        $reader->setDelimiter($delimiter);
        $records = $reader->getRecords();
        $createdWords = 0;
        $updatedWords = 0;
        $rejectedWords = 0;

        // collect data from csv file
        DB::beginTransaction();
        foreach ($records as $index => $record) {
            $lowerCaseWord = mb_strtolower($record[0]);

            // skip header if option is enabled
            if ($index === 0 && $skipHeader) {
                continue;
            }

            // reject word if contains space character
            if (str_contains($lowerCaseWord, ' ')) {
                $rejectedWords++;

                continue;
            }

            // reject word if it's too long
            if (mb_strlen($lowerCaseWord) >= 255) {
                $rejectedWords++;

                continue;
            }

            // reject word if word field is missing
            if (mb_strlen($lowerCaseWord) === 0) {
                $rejectedWords++;

                continue;
            }

            // reject word if it's stage is stage is an incorrect value
            $stage = isset($record[5]) ? $record[5] : 'learned';
            if (isset($record[5]) && !isset($stageMapping[$stage])) {
                $rejectedWords++;

                continue;
            }

            // try to retrieve word
            $encounteredWord = EncounteredWord::query()
                ->where('user_id', '=', $user->id)
                ->where('language', '=', $language->name)
                ->where('word', '=', $lowerCaseWord)
                ->first();

            // if does not exist, create it
            if (!$encounteredWord) {

                // reject word if does not exist and only update option is used
                if ($onlyUpdate) {
                    $rejectedWords++;

                    continue;
                }

                $encounteredWord = new EncounteredWord();
                $encounteredWord->user_id = $user->id;
                $encounteredWord->language = $language->name;
                $encounteredWord->word = $lowerCaseWord;
                $encounteredWord->translation = '';
                $encounteredWord->lemma = '';
                $encounteredWord->lemma_reading = '';
                $encounteredWord->reading = '';
                $encounteredWord->stage = 0;
                $encounteredWord->kanji = '';

                $createdWords++;
            } else {
                $updatedWords++;
            }

            // set translation
            if (isset($record[1])) {
                $encounteredWord->translation = $record[1];
            }

            // set lemma
            if (isset($record[2])) {
                $encounteredWord->lemma = $record[2];
            }

            // set reading
            if (isset($record[3])) {
                $encounteredWord->reading = $record[3];
            }

            // set lemma reading
            if (isset($record[4])) {
                $encounteredWord->lemma_reading = $record[4];
            }

            // set stage
            if (isset($record[5])) {
                $encounteredWord->setStage($stageMapping[$stage], true);
            }

            // save word with new data
            $encounteredWord->save();

            // add word to accepted words list
            $acceptedWords[] = $lowerCaseWord;
        }

        DB::commit();

        return new CsvImportResultData(
            createdWords: $createdWords,
            updatedWords: $updatedWords,
            rejectedWords: $rejectedWords,
        );
    }

    // TODO: rewrite with proper eloquent functions
    public function searchKanji(User $user, LanguageConfig $language, string $groupBy, bool $showUnknown): KanjiSearchResultData
    {
        $words = EncounteredWord::query()
            ->where('user_id', $user->id)
            ->where('stage', 0)
            ->where('language', $language->name)
            ->where('kanji', '<>', '')
            ->get();

        // get knwon kanji
        $knownKanji = [];
        foreach ($words as $word) {
            $wordKanji = preg_split('//u', $word->kanji, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($wordKanji as $currentKanji) {
                if (!in_array($currentKanji, $knownKanji, true)) {
                    array_push($knownKanji, $currentKanji);
                }
            }
        }

        // get kanji list
        if ($groupBy == 'grade') {
            $kanji = Kanji::where(function ($query) use ($knownKanji) {
                $query->where('grade', '>', 0)->orWhereIn('kanji', $knownKanji);
            });
        } else {
            $kanji = Kanji::where(function ($query) use ($knownKanji) {
                $query->where('jlpt', '>', 0)->orWhereIn('kanji', $knownKanji);
            });
        }

        if (!$showUnknown) {
            $kanji = $kanji->whereIn('kanji', $knownKanji);
        }

        $kanji = $kanji->get();

        // label kanji list
        foreach ($kanji as $currentKanji) {
            $currentKanji->known = in_array($currentKanji->kanji, $knownKanji);
        }

        // group kanji list
        if ($groupBy == 'grade') {
            $kanji = $kanji->groupBy('grade');
        } else {
            $kanji = $kanji->groupBy('jlpt');
        }

        // get count for statistics
        if ($groupBy == 'grade') {
            $totalCount = Kanji::select('grade', DB::raw('count(id) as total'))
                ->groupBy('grade')
                ->get()
                ->keyBy('grade');

            $knownCount = Kanji::select('grade', DB::raw('count(id) as total'))
                ->whereIn('kanji', $knownKanji)->groupBy('grade')
                ->get()
                ->keyBy('grade');
        } else {
            $totalCount = Kanji::select('jlpt', DB::raw('count(id) as total'))
                ->groupBy('jlpt')
                ->get()
                ->keyBy('jlpt');

            $knownCount = Kanji::select('jlpt', DB::raw('count(id) as total'))
                ->whereIn('kanji', $knownKanji)->groupBy('jlpt')
                ->get()
                ->keyBy('jlpt');
        }

        return new KanjiSearchResultData(
            $kanji,
            $totalCount,
            $knownCount
        );
    }

    public function getKanjiDetails(User $user, string $kanjiCharacter): KanjiData
    {
        $kanjiData = Kanji::query()
            ->where('kanji', '=', $kanjiCharacter)
            ->firstOrFail();

        $words = EncounteredWord::query()
            ->whereLike('word', '%' . $kanjiCharacter . '%')
            ->where('user_id', $user->id)
            ->limit(12)
            ->get();

        $radicals = Radical::query()
            ->select(['radicals'])
            ->where('kanji', '=', $kanjiCharacter)
            ->first();

        return new KanjiData(
            kanji: $kanjiData,
            words: $words,
            radicals: $radicals?->radicals
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\Vocabulary\CreateOrUpdateExampleSentenceRequest;
use App\Http\Requests\Vocabulary\CreatePhraseRequest;
use App\Http\Requests\Vocabulary\ExportToCsvRequest;
use App\Http\Requests\Vocabulary\GetKanjiDetailsRequest;
use App\Http\Requests\Vocabulary\ImportFromCsvRequest;
use App\Http\Requests\Vocabulary\SearchKanjiRequest;
use App\Http\Requests\Vocabulary\SearchVocabularyRequest;
use App\Http\Requests\Vocabulary\UpdatePhraseRequest;
use App\Http\Requests\Vocabulary\UpdateWordRequest;
use App\Http\Resources\Vocabulary\EncounteredWordResource;
use App\Http\Resources\Vocabulary\PhraseResource;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use App\Services\TempFileService;
use App\Services\VocabularyService;
use Illuminate\Support\Facades\Auth;

class VocabularyController extends Controller
{
    private $vocabularyService;

    private $tempFileService;

    public function __construct(VocabularyService $vocabularyService, TempFileService $tempFileService)
    {
        $this->vocabularyService = $vocabularyService;
        $this->tempFileService = $tempFileService;
    }

    // TODO: separate vocabulary/encountered word resource and phrase resource into their own controllers and services
    public function getUniqueWord(EncounteredWord $word)
    {
        $user = Auth::user();

        if ($user->id !== $word->user_id) {
            throw new \Exception('User has no permission to access this word.');
        }

        return new EncounteredWordResource($word);
    }

    public function updateWord(UpdateWordRequest $request, EncounteredWord $word)
    {
        $user = Auth::user();

        $wordData = collect($request->validated())->except('stage');

        $stage = $request->validated('stage');

        $this->vocabularyService->updateWord($user, $word, $wordData, $stage);

        return response()->noContent();
    }

    public function getPhrase(Phrase $phrase)
    {
        $user = Auth::user();

        if ($user->id !== $phrase->user_id) {
            throw new \Exception('User has no permission to access this phrase.');
        }

        return new PhraseResource($phrase);
    }

    public function createPhrase(CreatePhraseRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);
        $words = json_decode($request->words);
        $stage = $request->stage;
        $reading = is_null($request->reading) ? '' : $request->reading;
        $translation = is_null($request->translation) ? '' : $request->translation;

        // TODO: make phrase fields nullable
        $phraseId = $this->vocabularyService->createPhrase($user, $language, $words, $stage, $reading, $translation);

        return response()->json([
            'data' => $phraseId,
        ]);
    }

    public function updatePhrase(UpdatePhraseRequest $request, Phrase $phrase)
    {
        $user = Auth::user();

        $phraseData = collect($request->validated())->except('stage');
        $stage = $request->validated('stage');

        $this->vocabularyService->updatePhrase($user, $phrase, $phraseData, $stage);

        return response()->noContent();
    }

    public function deletePhrase(Phrase $phrase)
    {
        $user = Auth::user();

        $this->vocabularyService->deletePhrase($user, $phrase);

        return response()->noContent();
    }

    public function getWordExampleSentence(EncounteredWord $word)
    {
        $user = Auth::user();

        $exampleSentence = $this->vocabularyService->getExampleSentence($user, $word);

        return response()->json([
            'data' => $exampleSentence,
        ]);
    }

    public function getPhraseExampleSentence(Phrase $phrase)
    {
        $user = Auth::user();

        $exampleSentence = $this->vocabularyService->getExampleSentence($user, $phrase);

        return response()->json([
            'data' => $exampleSentence,
        ]);
    }

    public function createOrUpdateExampleSentence(CreateOrUpdateExampleSentenceRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);
        $targetType = $request->validated('targetType');
        $targetId = $request->validated('targetId');
        $exampleSentenceWords = json_decode($request->validated('exampleSentenceWords'));

        $this->vocabularyService->createOrUpdateExampleSentence(
            $user,
            $language,
            $targetType,
            $targetId,
            $exampleSentenceWords
        );

        return response()->noContent();
    }

    public function searchVocabulary(SearchVocabularyRequest $request)
    {
        $user = Auth::user();

        $searchResults = $this->vocabularyService->searchVocabulary(
            user: $user,
            language: LanguageConfig::load($user->selected_language),
            text: $request->validated('text'),
            bookId: $request->validated('book'),
            chapterId: $request->validated('chapter'),
            stage: $request->validated('stage'),
            phrases: $request->validated('phrases'),
            orderBy: $request->validated('orderBy'),
            translation: $request->validated('translation'),
            page: $request->validated('page')
        );

        return response()->json([
            'data' => $searchResults,
        ]);
    }

    public function exportToCsv(ExportToCsvRequest $request)
    {
        $user = Auth::user();

        $csv = $this->vocabularyService->exportToCsv(
            user: $user,
            language: LanguageConfig::load($user->selected_language),
            text: $request->validated('text'),
            bookId: $request->validated('book'),
            chapterId: $request->validated('chapter'),
            stage: $request->validated('stage'),
            phrases: $request->validated('phrases'),
            orderBy: $request->validated('orderBy'),
            translation: $request->validated('translation'),
            fields: $request->validated('fields')
        );

        $csv->output('vocabulary.csv');

        return response('', 200);
    }

    public function searchKanji(SearchKanjiRequest $request)
    {
        $user = Auth::user();

        $kanji = $this->vocabularyService->searchKanji(
            user: $user,
            language: LanguageConfig::load($user->selected_language),
            groupBy: $request->validated('kanjiGroupBy'),
            showUnknown: $request->validated('showUnknown')
        );

        return response()->json($kanji, 200);
    }

    public function getKanjiDetails(GetKanjiDetailsRequest $request)
    {

        $kanjiData = $this->vocabularyService->getkanjiDetails(
            user: Auth::user(),
            kanjiCharacter: $request->validated('kanji')
        );

        return response()->json($kanjiData, 200);
    }

    public function importFromCsv(ImportFromCsvRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load($user->selected_language);

        try {
            $fileName = $this->tempFileService->moveFileToTempFolder(
                user: $user,
                importFile: $request->file('importFile')
            );

            $importResponseData = $this->vocabularyService->importFromCsv(
                user: $user,
                language: $language,
                fileName: $fileName,
                delimiter: $request->validated('delimiter'),
                onlyUpdate: $request->validated('onlyUpdate'),
                skipHeader: $request->validated('skipHeader')
            );
        } catch (\Throwable $error) {
            $this->tempFileService->deleteTempFile($fileName);

            throw $error;
        }

        $this->tempFileService->deleteTempFile($fileName);

        return response()->json([
            'data' => $importResponseData,
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Library;

use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\Chapters\FinishChapterRequest;
use App\Http\Requests\Chapters\StoreChapterRequest;
use App\Http\Requests\Chapters\UpdateChapterRequest;
use App\Http\Resources\Chapter\ChapterResource;
use App\Http\Resources\Chapter\ChapterResourceCollection;
use App\Models\Book;
use App\Models\Chapter;
use App\Services\ChapterService;
use Illuminate\Support\Facades\Auth;
use Laravel\Horizon\Http\Controllers\Controller;

class ChapterController extends Controller
{
    public function __construct(private ChapterService $chapterService)
    {
        //
    }

    public function index(Book $book)
    {
        $user = Auth::user();

        $chapters = $this->chapterService->getChaptersForBook($user, $book);

        return new ChapterResourceCollection($chapters);
    }

    public function show(Chapter $chapter)
    {
        $user = Auth::user();

        $transformedChapter = $this->chapterService->getChapterForEditor($user, $chapter);

        return new ChapterResource($transformedChapter);
    }

    public function showForReader(Chapter $chapter)
    {
        $user = Auth::user();
        $language = LanguageConfig::load($user->selected_language);

        $chapter = $this->chapterService->getChapterForReader($user, $language, $chapter);

        return response()->json($chapter, 200);
    }

    public function store(StoreChapterRequest $request, Book $book)
    {
        $user = Auth::user();
        $text = $request->validated('text');
        $name = $request->validated('name');

        $this->chapterService->createChapter(
            $user,
            $book,
            $name,
            $text ?? ''
        );

        return response()->json('Chapter has been created successfully.', 200);
    }

    public function update(UpdateChapterRequest $request, Chapter $chapter)
    {
        $user = Auth::user();
        $text = $request->validated('text');
        $name = $request->validated('name');

        $this->chapterService->updateChapter(
            $user,
            $chapter,
            $name,
            $text ?? ''
        );

        return response()->noContent();
    }

    public function destroy(Chapter $chapter)
    {
        $user = Auth::user();

        $this->chapterService->deleteChapter($user, $chapter);

        return response()->noContent();
    }

    public function finish(FinishChapterRequest $request, Chapter $chapter)
    {
        $user = Auth::user();
        $uniqueWords = json_decode($request->validated('uniqueWords'));
        $autoLevelUpWords = (bool) $request->validated('autoLevelUpWords');
        $leveledUpWords = json_decode($request->validated('leveledUpWords'));
        $leveledUpPhrases = json_decode($request->validated('leveledUpPhrases'));
        $autoMoveWordsToKnown = (bool) $request->validated('autoMoveWordsToKnown');

        $this->chapterService->finishChapter(
            user: $user,
            chapter: $chapter,
            autoMoveWordsToKnown: $autoMoveWordsToKnown,
            uniqueWords: $uniqueWords,
            autoLevelUpWords: $autoLevelUpWords,
            leveledUpWords: $leveledUpWords,
            leveledUpPhrases: $leveledUpPhrases
        );

        return response()->noContent();
    }

    public function wordCounts(Book $book)
    {
        $user = Auth::user();

        $this->chapterService->getWordCounts($user, $book);

        return response()->noContent();
    }
}

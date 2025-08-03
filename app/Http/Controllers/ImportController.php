<?php

namespace App\Http\Controllers;

use App\Enums\Import\EbookChapterSortMethodEnum;
use App\Enums\Import\ImportTypeEnum;
use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\Import\ImportRequest;
use App\Models\Book;
use App\Services\ImportService;
use Illuminate\Support\Facades\Auth;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService,
    ) {
        //
    }

    public function import(ImportRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load($user->selected_language);
        $book = Book::find($request->validated('bookId'));
        $importType = ImportTypeEnum::from($request->validated('importType'));
        $eBookChapterSortMethod = EbookChapterSortMethodEnum::from($request->validated('eBookChapterSortMethod'));
        $bookName = $request->validated('bookName');
        $chapterName = $request->validated('chapterName');
        $chunkSize = intval($request->validated('maximumCharactersPerChapter'));
        $importFile = $request->file('importFile') ?? null;
        $importText = $request->validated('importText') ?? null;
        $importSubtitles = $request->validated('importSubtitles') ?? null;

        $this->importService->import(
            importType: $importType,
            user: $user,
            language: $language,
            chunkSize: $chunkSize,
            eBookChapterSortMethod: $eBookChapterSortMethod,
            chapterName: $chapterName,
            book: $book ?? null,
            bookName: $bookName ?? null,
            importFile: $importFile,
            importText: $importText,
            importSubtitles: $importSubtitles,
        );

        return response()->noContent();
    }
}

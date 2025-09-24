<?php

namespace App\Http\Controllers\Vocabulary;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vocabulary\ExportVocabularyRequest;
use App\Http\Requests\Vocabulary\ImportVocabularyRequest;
use App\Services\TempFileService;
use App\Services\Vocabulary\VocabularyImportExportService;
use Illuminate\Support\Facades\Auth;

class VocabularyImportExportController extends Controller
{
    public function __construct(
        private VocabularyImportExportService $vocabularyImportExportService,
        private TempFileService $tempFileService
    ) {
        //
    }

    public function export(ExportVocabularyRequest $request)
    {
        $user = Auth::user();

        $csv = $this->vocabularyImportExportService->export(
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

    public function import(ImportVocabularyRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load($user->selected_language);

        try {
            $fileName = $this->tempFileService->moveFileToTempFolder(
                user: $user,
                importFile: $request->file('importFile')
            );

            $importResponseData = $this->vocabularyImportExportService->import(
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

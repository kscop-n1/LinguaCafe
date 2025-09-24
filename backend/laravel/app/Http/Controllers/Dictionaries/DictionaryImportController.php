<?php

namespace App\Http\Controllers\Dictionaries;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dictionaries\Import\ImportDictionaryCsvFileRequest;
use App\Http\Requests\Dictionaries\Import\ImportSupportedDictionaryRequest;
use App\Http\Requests\Dictionaries\Import\ValidateDictionaryCsvFileRequest;
use App\Http\Requests\Dictionaries\Import\ValidateFileRequest;
use App\Services\Dictionaries\DictionaryImportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DictionaryImportController extends Controller
{
    public function __construct(
        private DictionaryImportService $dictionaryImportService
    ) {
        //
    }

    public function validate(ValidateFileRequest $request)
    {
        $dictionaryFile = $request->file('dictionaryFile');
        $languageConfigs = LanguageConfig::all();

        $dictCcLanguageCodes = $languageConfigs
            ->whereNotNull('dictCcCode')
            ->pluck('name', 'dictCcCode')
            ->toArray();

        $databaseLanguageCodes = $languageConfigs
            ->whereNotNull('databaseDictionaryTableName')
            ->pluck('databaseDictionaryTableName', 'name')
            ->toArray();

        $supportedSourceLanguages = $languageConfigs
            ->where('linguacafeSupport', '=', true)
            ->pluck('name')
            ->toArray();

        $dictionaryFound = $this->dictionaryImportService->getDictionaryFileInformation(
            $dictionaryFile,
            $supportedSourceLanguages,
            $dictCcLanguageCodes,
            $databaseLanguageCodes
        );

        return response()->json([
            'data' => $dictionaryFound,
        ]);
    }

    public function import(ImportSupportedDictionaryRequest $request)
    {
        set_time_limit(2400);
        $user = Auth::user();
        $dictionaryName = $request->validated('dictionaryName');
        $dictionaryFileName = $request->validated('dictionaryFileName');
        $dictionarySourceLanguage = $request->validated('dictionarySourceLanguage');
        $dictionaryTargetLanguage = $request->validated('dictionaryTargetLanguage');
        $dictionaryDatabaseName = $request->validated('dictionaryDatabaseName');

        try {
            $this->dictionaryImportService->importSupportedDictionary(
                $user,
                $dictionaryName,
                $dictionaryFileName,
                $dictionarySourceLanguage,
                $dictionaryTargetLanguage,
                $dictionaryDatabaseName
            );
        } catch (\Throwable $error) {
            if ($dictionaryName !== 'JMDict') {
                DB::table('dictionaries')
                    ->where('database_table_name', $dictionaryDatabaseName)
                    ->delete();

                Schema::dropIfExists($dictionaryDatabaseName);
            }

            throw $error;
        }

        return response()->noContent();
    }

    public function validateCsv(ValidateDictionaryCsvFileRequest $request)
    {
        $file = $request->file('dictionary');
        $delimiter = $request->post('delimiter');
        $skipHeader = boolval($request->post('skipHeader') === 'true');

        $sample = $this->dictionaryImportService->testDictionaryCsvFile($file, $delimiter, $skipHeader);

        return response()->json([
            'data' => $sample,
        ]);
    }

    public function importCsv(ImportDictionaryCsvFileRequest $request)
    {
        set_time_limit(2400);
        $file = $request->file('dictionary');
        $skipHeader = boolval($request->validated('skipHeader') === 'true');
        $delimiter = $request->validated('delimiter');
        $dictionaryName = $request->validated('dictionaryName');
        $databaseTableName = $request->validated('databaseName');
        $sourceLanguage = LanguageConfig::load($request->validated('sourceLanguage'));
        $targetLanguage = LanguageConfig::load($request->validated('targetLanguage'));
        $color = $request->validated('color');

        $this->dictionaryImportService->importDictionaryCsvFile(
            $file,
            $skipHeader,
            $delimiter,
            $dictionaryName,
            $databaseTableName,
            $sourceLanguage,
            $targetLanguage,
            $color
        );

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Dictionary;
use Illuminate\Support\Facades\DB;
use App\Services\DictionaryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

// services
use App\Helpers\Language\LanguageConfig;
use App\Services\DictionaryImportService;

// request classes
use App\Http\Requests\Dictionaries\SearchApiRequest;
use App\Http\Resources\Dictionary\DictionaryResource;
use App\Http\Requests\Dictionaries\DeleteDictionaryRequest;
use App\Http\Requests\Dictionaries\UpdateDictionaryRequest;
use App\Http\Requests\Dictionaries\SearchDefinitionsRequest;
use App\Http\Requests\Dictionaries\SearchInflectionsRequest;
use App\Http\Resources\Dictionary\DictionaryResourceCollection;
use App\Http\Requests\Dictionaries\CreateDeeplDictionaryRequest;
use App\Http\Requests\Dictionaries\TestDictionaryCsvFileRequest;
use App\Http\Requests\Dictionaries\ImportDictionaryCsvFileRequest;
use App\Http\Requests\Dictionaries\CreateMyMemoryDictionaryRequest;
use App\Http\Requests\Dictionaries\GetDictionaryRecordCountRequest;
use App\Http\Requests\Dictionaries\CreateCustomApiDictionaryRequest;
use App\Http\Requests\Dictionaries\ImportSupportedDictionaryRequest;
use App\Http\Requests\Dictionaries\GetDictionaryFileInformationRequest;
use App\Http\Requests\Dictionaries\CreateLibreTranslateDictionaryRequest;
use App\Http\Requests\Dictionaries\SearchDefinitionsForHoverVocabularyRequest;

class DictionaryController extends Controller
{
    private $dictionaryService;
    private $dictionaryImportService;
    
    //TODO: Should be separated into 3 files: DictionaryResourceController, DictionarySearchController and DictionaryImportController. Same for services
    public function __construct(DictionaryService $dictionaryService, DictionaryImportService $dictionaryImportService) {
        $this->dictionaryService = $dictionaryService;
        $this->dictionaryImportService = $dictionaryImportService;
    }

    public function getDictionaries() {
        $dictionaries = $this->dictionaryService->getDictionaries();

        return new DictionaryResourceCollection($dictionaries);
    }

    public function getDictionary(Dictionary $dictionary) {
        $dictionary->loadRecordCount();

        return new DictionaryResource($dictionary);
    }

    public function updateDictionary(UpdateDictionaryRequest $request, Dictionary $dictionary) {
        $dictionaryData = collect($request->validated());
        $dictionaryData = $dictionaryData->reject(function($dictionaryData) {
            return is_null($dictionaryData);
        });
        
        $this->dictionaryService->updateDictionary($dictionary, $dictionaryData);

        return response()->noContent();
    }

    public function isAnyApiDictionaryEnabled() {
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $isAnyApiDictionaryEnabled = $this->dictionaryService->isAnyApiDictionaryEnabled($language);
        
        return response()->json([
            'data' => $isAnyApiDictionaryEnabled,
        ]);
    }

    public function getDeeplCharacterLimit() {
        $deeplLimit = $this->dictionaryService->getDeeplCharacterLimit();   
        
        return response()->json([
            'data' => $deeplLimit,
        ]);
    }

    public function searchDefinitions(SearchDefinitionsRequest $request) {
        $language = LanguageConfig::load($request->validated('language'));
        $term = $request->validated('term');

        $searchResult = $this->dictionaryService->searchDefinitions($language, $term);

        return response()->json([
            'data' => $searchResult,
        ]);
    }

    public function searchDefinitionsForHoverVocabulary(SearchDefinitionsForHoverVocabularyRequest $request) {
        $language = LanguageConfig::load($request->validated('language'));
        $term = $request->validated('term');

        $searchResult = $this->dictionaryService->searchDefinitionsForHoverVocabulary($language, $term);

        return response()->json([
            'data' => $searchResult,
        ]);
    }

    public function searchApiDictionaries(SearchApiRequest $request) {
        $language = LanguageConfig::load($request->validated('language'));
        $term = $request->validated('term');
        $context = $request->validated('context') ? $request->post('context') : '';

        $definitions = $this->dictionaryService->searchApiDictionaries($language, $term, $context);

        return response()->json([
                'data' => $definitions,
        ]);
    }

    public function searchInflections(SearchInflectionsRequest $request) {
        $term = $request->term;

        $inflections = $this->dictionaryService->searchInflections($term);

        return response()->json([
            'data' => $inflections,
        ]);
    }

    public function createDeeplDictionary(CreateDeeplDictionaryRequest $request) {
        $sourceLanguage = LanguageConfig::load($request->validated('sourceLanguage'));
        $targetLanguage = LanguageConfig::load($request->validated('targetLanguage'));
        $color = $request->validated('color');
        $name  = $request->validated('name');

        $this->dictionaryImportService->createDeeplDictionary($sourceLanguage, $targetLanguage, $color, $name);

        return response()->noContent();
    }

    public function createMyMemoryDictionary(CreateMyMemoryDictionaryRequest $request) {
        $sourceLanguage = LanguageConfig::load($request->validated('sourceLanguage'));
        $targetLanguage = LanguageConfig::load($request->validated('targetLanguage'));
        $color = $request->validated('color');
        $name  = $request->validated('name');

        $this->dictionaryImportService->createMyMemoryDictionary($sourceLanguage, $targetLanguage, $color, $name);
        
        return response()->noContent();
    }
    
    public function createLibreTranslateDictionary(CreateLibreTranslateDictionaryRequest $request) {
        $sourceLanguage = LanguageConfig::load($request->validated('sourceLanguage'));
        $targetLanguage = LanguageConfig::load($request->validated('targetLanguage'));
        $color = $request->validated('color');
        $name  = $request->validated('name');

        $this->dictionaryImportService->createLibreTranslateDictionary($sourceLanguage, $targetLanguage, $color, $name);
        
        return response()->noContent();
    }

    public function createCustomApiDictionary(CreateCustomApiDictionaryRequest $request) {
        $sourceLanguage = LanguageConfig::load($request->validated('sourceLanguage'));
        $targetLanguage = LanguageConfig::load($request->validated('targetLanguage'));
        $color = $request->validated('color');
        $name  = $request->validated('name');
        $host  = $request->validated('api_host');

        $this->dictionaryImportService->createCustomApiDictionary($sourceLanguage, $targetLanguage, $color, $name, $host);

        return response()->noContent();
    }

    public function testDictionaryCsvFile(TestDictionaryCsvFileRequest $request) {
        $file = $request->file('dictionary');
        $delimiter = $request->post('delimiter');
        $skipHeader = boolval($request->post('skipHeader') === 'true');

        $sample = $this->dictionaryImportService->testDictionaryCsvFile($file, $delimiter, $skipHeader);

        return response()->json([
            'data' => $sample,
        ]);
    }

    public function importDictionaryCsvFile(ImportDictionaryCsvFileRequest $request) {
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

    public function getDictionaryFileInformation(GetDictionaryFileInformationRequest $request) {
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

    public function importSupportedDictionary(ImportSupportedDictionaryRequest $request) {
        set_time_limit(2400);
        $userUuid = Auth::user()->uuid;
        $dictionaryName = $request->post('dictionaryName');
        $dictionaryFileName = $request->post('dictionaryFileName');
        $dictionarySourceLanguage = $request->post('dictionarySourceLanguage');
        $dictionaryTargetLanguage = $request->post('dictionaryTargetLanguage');
        $dictionaryDatabaseName = $request->post('dictionaryDatabaseName');
        
        try {
            $this->dictionaryImportService->importSupportedDictionary(
                $userUuid, 
                $dictionaryName, 
                $dictionaryFileName, 
                $dictionarySourceLanguage, 
                $dictionaryTargetLanguage, 
                $dictionaryDatabaseName
            );
        } catch (\Throwable $t) {
            if ($dictionaryName !== 'JMDict') {
                DB
                    ::table('dictionaries')
                    ->where('database_table_name', $dictionaryDatabaseName)
                    ->delete();

                Schema::dropIfExists($dictionaryDatabaseName);
            }
            
            abort(500, $t->getMessage());
        } catch (\Exception $e) {
            if ($dictionaryName !== 'JMDict') {
                DB
                    ::table('dictionaries')
                    ->where('database_table_name', $dictionaryDatabaseName)
                    ->delete();

                Schema::dropIfExists($dictionaryDatabaseName);
            }
            
            abort(500, $e->getMessage());
        }

        return response()->json('Dictionary has been imported successfully.', 200);
    }

    public function getDictionaryRecordCount($dictionaryTableName, GetDictionaryRecordCountRequest $request) {
        try {
            $recordCount = $this->dictionaryService->getDictionaryRecordCount($dictionaryTableName);
        } catch(\Exception $e) {
            abort(500, $e->getMessage());
        }

        return response()->json($recordCount, 200);
    }

    public function deleteDictionary($dictionaryId, DeleteDictionaryRequest $request) {
        try {
            $this->dictionaryService->deleteDictionary($dictionaryId);
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        return response()->json('Dictionary has been deleted successfully.', 200);
    }
}

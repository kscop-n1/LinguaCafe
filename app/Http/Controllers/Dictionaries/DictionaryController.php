<?php

namespace App\Http\Controllers\Dictionaries;

use App\Enums\DictionaryTypeEnum;
use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dictionaries\StoreApiDictionaryRequest;
use App\Http\Requests\Dictionaries\UpdateDictionaryRequest;
use App\Http\Resources\Dictionary\DictionaryResource;
use App\Http\Resources\Dictionary\DictionaryResourceCollection;
use App\Models\Dictionary;
use App\Services\Dictionaries\DictionaryImportService;
use App\Services\DictionaryService;
use Illuminate\Support\Facades\Auth;

class DictionaryController extends Controller
{
    // TODO: Should be separated into 3 files: DictionaryResourceController, DictionarySearchController and DictionaryImportController. Same for services
    public function __construct(
        private DictionaryService $dictionaryService,
        private DictionaryImportService $dictionaryImportService
    ) {
        //
    }

    public function index()
    {
        $dictionaries = $this->dictionaryService->getDictionaries();

        return new DictionaryResourceCollection($dictionaries);
    }

    public function show(Dictionary $dictionary)
    {
        $dictionary->loadRecordCount();

        return new DictionaryResource($dictionary);
    }

    public function update(UpdateDictionaryRequest $request, Dictionary $dictionary)
    {
        $dictionaryData = collect($request->validated());
        $dictionaryData = $dictionaryData->reject(function ($dictionaryData) {
            return is_null($dictionaryData);
        });

        $this->dictionaryService->updateDictionary($dictionary, $dictionaryData);

        return response()->noContent();
    }

    public function isAnyApiDictionaryEnabled()
    {
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $isAnyApiDictionaryEnabled = $this->dictionaryService->isAnyApiDictionaryEnabled($language);

        return response()->json([
            'data' => $isAnyApiDictionaryEnabled,
        ]);
    }

    public function deeplUsage()
    {
        $deeplLimit = $this->dictionaryService->getDeeplCharacterLimit();

        return response()->json([
            'data' => $deeplLimit,
        ]);
    }

    public function storeApi(StoreApiDictionaryRequest $request)
    {
        $sourceLanguage = LanguageConfig::load($request->validated('sourceLanguage'));
        $targetLanguage = LanguageConfig::load($request->validated('targetLanguage'));
        $color = $request->validated('color');
        $name = $request->validated('name');
        $type = DictionaryTypeEnum::from($request->validated('type'));
        $apiHost = $request->validated('api_host');

        $this->dictionaryImportService->storeApiDictionary(
            $sourceLanguage,
            $targetLanguage,
            $color,
            $name,
            $type,
            $apiHost
        );

        return response()->noContent();
    }

    public function destroy(Dictionary $dictionary)
    {
        $this->dictionaryService->deleteDictionary($dictionary);

        return response()->noContent();
    }
}

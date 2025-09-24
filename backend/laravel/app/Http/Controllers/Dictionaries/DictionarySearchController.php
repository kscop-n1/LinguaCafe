<?php

namespace App\Http\Controllers\Dictionaries;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dictionaries\Search\SearchApiRequest;
use App\Http\Requests\Dictionaries\Search\SearchHoverRequest;
use App\Http\Requests\Dictionaries\Search\SearchInflectionsRequest;
use App\Http\Requests\Dictionaries\Search\SearchRequest;
use App\Services\DictionaryService;

class DictionarySearchController extends Controller
{
    public function __construct(
        private DictionaryService $dictionaryService,
    ) {
        //
    }

    public function search(SearchRequest $request)
    {
        $language = LanguageConfig::load($request->validated('language'));
        $term = $request->validated('term');

        $searchResult = $this->dictionaryService->searchDefinitions($language, $term);

        return response()->json([
            'data' => $searchResult,
        ]);
    }

    public function searchHover(SearchHoverRequest $request)
    {
        $language = LanguageConfig::load($request->validated('language'));
        $term = $request->validated('term');

        $searchResult = $this->dictionaryService->searchDefinitionsForHoverVocabulary($language, $term);

        return response()->json([
            'data' => $searchResult,
        ]);
    }

    public function searchApi(SearchApiRequest $request)
    {
        $language = LanguageConfig::load($request->validated('language'));
        $term = $request->validated('term');
        $context = $request->validated('context') ? $request->post('context') : '';

        $definitions = $this->dictionaryService->searchApiDictionaries($language, $term, $context);

        return response()->json([
            'data' => $definitions,
        ]);
    }

    public function searchInflections(SearchInflectionsRequest $request)
    {
        $term = $request->term;

        $inflections = $this->dictionaryService->searchInflections($term);

        return response()->json([
            'data' => $inflections,
        ]);
    }
}

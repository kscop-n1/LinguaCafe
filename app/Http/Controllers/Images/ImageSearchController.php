<?php

namespace App\Http\Controllers\Images;

use App\Http\Controllers\Controller;
use App\Services\Images\ImageSearchService;

class ImageSearchController extends Controller
{
    public function __invoke(string $searchEngine, string $searchTerm)
    {
        $images = (new ImageSearchService())->search($searchTerm);

        return [
            'data' => $images,
        ];
    }
}

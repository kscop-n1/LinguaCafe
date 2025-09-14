<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\GetWebsiteTextRequest;
use App\Services\Website\WebsiteService;

class WebsiteController extends Controller
{
    public function __construct(
        private WebsiteService $websiteService
    ) {
        //
    }

    public function getWebsiteText(GetWebsiteTextRequest $request)
    {
        $url = $request->post('url');

        $websiteText = $this->websiteService->getWebsiteText($url);

        return response()->json([
            'data' => $websiteText,
        ]);
    }
}

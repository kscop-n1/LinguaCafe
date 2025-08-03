<?php

namespace App\Http\Controllers\Youtube;

use App\Http\Controllers\Controller;
use App\Http\Requests\Youtube\GetYoutubeSubtitlesRequest;
use App\Services\Youtube\YoutubeService;

class YoutubeController extends Controller
{
    public function __construct(
        private YoutubeService $youtubeService,
    ) {
        //
    }

    public function getYoutubeSubtitles(GetYoutubeSubtitlesRequest $request)
    {
        $url = $request->post('url');

        $subtitleList = $this->youtubeService->getYoutubeSubtitles($url);

        return response()->json([
            'data' => $subtitleList,
        ]);
    }
}

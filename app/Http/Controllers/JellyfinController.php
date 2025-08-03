<?php

namespace App\Http\Controllers;

use App\Services\JellyfinService;

class JellyfinController extends Controller
{
    public function __construct(
        private JellyfinService $jellyfinService
    ) {
        //
    }

    public function getJellyfinCurrentlyPlayedSubtitles()
    {
        $subtitles = $this->jellyfinService->getJellyfinCurrentlyPlayedSubtitles();

        return response()->json([
            'data' => $subtitles,
        ]);
    }
}

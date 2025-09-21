<?php

namespace App\Http\Controllers\Jellyfin;

use App\Http\Controllers\Controller;
use App\Services\JellyfinService;

class JellyfinController extends Controller
{
    public function __construct(
        private JellyfinService $jellyfinService
    ) {
        //
    }

    public function index()
    {
        $subtitles = $this->jellyfinService->getJellyfinCurrentlyPlayedSubtitles();

        return response()->json([
            'data' => $subtitles,
        ]);
    }
}

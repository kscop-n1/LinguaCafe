<?php

namespace App\Services\Youtube;

use Illuminate\Support\Facades\Http;

class YoutubeService
{
    private $pythonServiceHost;

    public function __construct()
    {
        $this->pythonServiceHost = env('PYTHON_CONTAINER_NAME', 'linguacafe-python-service');
    }

    public function getYoutubeSubtitles($url): array
    {
        $subtitleList = Http::post($this->pythonServiceHost . ':8678/youtube/get-subtitle-list', [
            'url' => $url,
        ]);

        return json_decode($subtitleList);
    }
}

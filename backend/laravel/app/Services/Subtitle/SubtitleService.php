<?php

namespace App\Services\Subtitle;

use Illuminate\Support\Facades\Http;

class SubtitleService
{
    private $pythonServiceHost;

    public function __construct()
    {
        $this->pythonServiceHost = env('PYTHON_CONTAINER_NAME', 'linguacafe-python-service');
    }

    public function getSubtitleFileContent($fileName): array
    {
        $subtitleContent = Http::post($this->pythonServiceHost . ':8678/subtitles/read', [
            'fileName' => $fileName,
        ]);

        $subtitleContent->throwUnlessStatus(200);

        return json_decode($subtitleContent);
    }
}

<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\Http;

class WebsiteService
{
    private $pythonServiceHost;

    public function __construct()
    {
        $this->pythonServiceHost = env('PYTHON_CONTAINER_NAME', 'linguacafe-python-service');
    }

    public function getWebsiteText($url): string
    {
        $websiteText = Http::post($this->pythonServiceHost . ':8678/web/get-website-text', [
            'url' => $url,
        ]);

        return json_decode($websiteText);
    }
}

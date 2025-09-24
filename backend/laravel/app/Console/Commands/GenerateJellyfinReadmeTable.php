<?php

namespace App\Console\Commands;

use App\Helpers\Language\LanguageConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateJellyfinReadmeTable extends Command
{
    protected $signature = 'languages:generate-jellyfin-readme';

    protected $description = 'Generates a markdown table for the readme from the language config file.';

    public function handle(): void
    {
        $languages = LanguageConfig::all()
            ->whereNotNull('jellyfinFilenameSlug')
            ->where('linguacafeSupport', true);

        $readmeTable = $this->getHeader();

        $languages->each(function (LanguageConfig $language) use (&$readmeTable) {
            $readmeTable .= '|';
            $readmeTable .= Str::ucfirst($language->name) . '|';
            $readmeTable .= $language->jellyfinFilenameSlug . "|\r\n";
        });

        echo $readmeTable;
    }

    private function getHeader(): string
    {
        $header = "| Language | Language Code |\r\n";
        $headerDivider = "| :--- | ---- |\r\n";

        return $header . $headerDivider;
    }
}

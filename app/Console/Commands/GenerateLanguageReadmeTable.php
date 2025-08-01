<?php

namespace App\Console\Commands;

use App\Helpers\Language\LanguageConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateLanguageReadmeTable extends Command
{
    protected $signature = 'languages:generate-language-readme';

    protected $description = 'Generates a markdown table for the readme from the language config file.';

    public function handle(): void
    {
        $languages = LanguageConfig::all()->where('linguacafeSupport', true);

        $readmeTable = $this->getHeader();

        $languages->each(function (LanguageConfig $language) use (&$readmeTable) {
            $readmeTable .= '|';
            $readmeTable .= "<img src='images/flags/" . $language->name . ".png' width='25'> |";
            $readmeTable .= Str::ucfirst($language->name) . '|';
            $readmeTable .= $language->getFullDictionaryList()->join(', ') . "|\r\n";
        });

        echo $readmeTable;
    }

    private function getHeader(): string
    {
        $header = "| Flag | Language  | Dictionaries |\r\n";
        $headerDivider = "|:---:|:---:|---|\r\n";

        return $header . $headerDivider;
    }
}

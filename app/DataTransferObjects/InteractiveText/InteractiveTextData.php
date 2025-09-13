<?php

namespace App\DataTransferObjects\InteractiveText;

use Illuminate\Support\Collection;

class InteractiveTextData
{
    public function __construct(
        public Collection $words,
        public Collection $uniqueWords,
        public Collection $phrases,
    ) {
        //
    }
}

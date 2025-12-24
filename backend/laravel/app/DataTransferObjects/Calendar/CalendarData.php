<?php

namespace App\DataTransferObjects\Calendar;

use Illuminate\Support\Collection;

class CalendarData
{
    public function __construct(
        public Collection $goals,
        public Collection $reviews,
    ) {
        //
    }
}

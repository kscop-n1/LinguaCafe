<?php

namespace App\Enums\Import;

/*
    These are import types going to the python microservice. ImportTypeEnum represents unique import options on the frontend,
    while TokenizerRequestTypeEnum represents import options that are programmatically different on the python side.
*/
enum TokenizerRequestTypeEnum: string
{
    case E_BOOK = 'e-book';
    case SUBTITLE = 'subtitle';
    case TEXT = 'text';
}

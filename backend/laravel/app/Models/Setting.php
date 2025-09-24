<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public function decode()
    {
        // TODO: check how it handles floats
        return json_decode($this->value);
    }
}

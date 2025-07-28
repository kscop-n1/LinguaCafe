<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dictionary extends Model
{
    use HasFactory;
    protected $table = 'dictionaries';
    protected $fillable = [
        'name',
        'api_host',
        'source_language',
        'target_language',
        'color',
        'enabled',
    ];

    public function loadRecordCount(): void
    {
        if ($this->database_table_name == 'API') {
            $this->records = '-';
        } else {
            $records = DB
                ::table($this->database_table_name)
                ->selectRaw('count(*) as record_count')
                ->get();

            $this->records = $records[0]->record_count;
        }
    }
}
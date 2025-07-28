<?php

namespace App\Http\Resources\Dictionary;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DictionaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'api_host' => $this->api_host,
            'database_table_name' => $this->database_table_name,
            'source_language' => $this->source_language,
            'target_language' => $this->target_language,
            'color' => $this->color,
            'enabled' => $this->enabled,
            'records' => $this->when(isset($this->records), $this->records),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

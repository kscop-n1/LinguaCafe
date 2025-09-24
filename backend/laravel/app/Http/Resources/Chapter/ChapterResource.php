<?php

namespace App\Http\Resources\Chapter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
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
            'user_id' => $this->user_id,
            'book_id' => $this->book_id,
            'read_count' => $this->read_count,
            'word_count' => $this->word_count,
            'name' => $this->name,
            'language' => $this->language,
            'raw_text' => $this->raw_text,
            'processed_text' => $this->processed_text ? $this->getProcessedText() : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'unique_words' => $this->unique_words,
            'unique_word_ids' => $this->unique_word_ids,
            'processing_status' => $this->processing_status,

            'type' => $this->type,
            'subtitle_timestamps' => $this->subtitle_timestamps,

            // TODO: since its always empty, it should be removed and only be in websockets
            'wordCount' => $this->when(isset($this->wordCount), $this->wordCount),
        ];
    }
}

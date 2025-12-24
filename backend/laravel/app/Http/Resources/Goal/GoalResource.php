<?php

namespace App\Http\Resources\Goal;

use App\Helpers\Language\LanguageConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
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
            'user_id' => $this->user_id,
            'language' => $this->language ? LanguageConfig::load($this->language) : null,
            'type' => $this->type,
            'target_id' => $this->target_id,
            'current_chapter' => $this->current_chapter,
            'quantity' => $this->quantity,
            'todays_quantity' => $this->when(isset($this->todays_quantity), $this->todays_quantity),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'goalAchievements' => $this->whenLoaded('goalAchievements'),
        ];
    }
}

<?php

namespace App\Http\Resources\Goal;

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
            'language' => $this->language,
            'type' => $this->type,
            'target_id' => $this->target_id,
            // TODO: probably can be removed
            'current_chapter' => $this->current_chapter,
            'quantity' => $this->quantity,
            'todaysQuantity' => $this->when(isset($this->todaysQuantity), $this->todaysQuantity),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

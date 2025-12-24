<?php

namespace App\Http\Resources\Goal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalAlchievementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}

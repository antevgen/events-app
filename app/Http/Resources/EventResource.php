<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Event
 */
class EventResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'recurrent' => $this->recurrent,
            'frequency' => $this->when($this->recurrent, $this->frequency),
            'repeat_until' => $this->when($this->recurrent, $this->repeat_until),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'events' => EventResource::collection($this->whenLoaded('events')),
        ];
    }
}

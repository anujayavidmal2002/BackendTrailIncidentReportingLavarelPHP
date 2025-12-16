<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
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
            'type' => $this->type,
            'description' => $this->description,
            'location' => $this->location,
            'locationText' => $this->locationText,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'severity' => $this->severity,
            'status' => $this->status,
            'date' => $this->date,
            'time' => $this->time,
            'photos' => $this->photos,
            'photoUrl' => $this->photoUrl,
            'photoKey' => $this->photoKey,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

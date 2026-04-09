<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SwapRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'note' => $this->note,
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
            ]),
            'target' => $this->whenLoaded('target', fn () => [
                'id' => $this->target->id,
                'name' => $this->target->name,
            ]),
            'requester_shift' => new ShiftResource($this->whenLoaded('requesterShift')),
            'target_shift' => new ShiftResource($this->whenLoaded('targetShift')),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ] : null),
            'peer_responded_at' => $this->peer_responded_at,
            'manager_decided_at' => $this->manager_decided_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

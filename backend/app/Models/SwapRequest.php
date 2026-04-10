<?php

namespace App\Models;

use App\Enums\SwapRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'requester_id', 'target_id', 'requester_shift_id', 'target_shift_id',
    'type', 'status', 'peer_responded_at', 'manager_decided_at', 'manager_id', 'note',
])]
class SwapRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SwapRequestStatus::class,
            'peer_responded_at' => 'datetime',
            'manager_decided_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function requesterShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'requester_shift_id');
    }

    public function targetShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'target_shift_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function isGiveaway(): bool
    {
        return $this->type === 'giveaway';
    }
}

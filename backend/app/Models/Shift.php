<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'shift_template_id', 'date', 'notes'])]
class Shift extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function scopeForWeek(Builder $query, string $weekStart): Builder
    {
        $start = \Carbon\Carbon::parse($weekStart)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}

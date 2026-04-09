<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'status',
        'note',
        'manager_id',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => LeaveStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function getBusinessDaysAttribute(): int
    {
        $start = $this->start_date->copy();
        $end = $this->end_date->copy();
        $days = 0;

        while ($start->lte($end)) {
            if (!$start->isWeekend()) {
                $days++;
            }
            $start->addDay();
        }

        return $days;
    }
}

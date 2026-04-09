<?php

namespace App\Models;

use App\Enums\DayType;
use App\Enums\ShiftType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['day_type', 'shift_type', 'start_time', 'end_time'])]
class ShiftTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'day_type' => DayType::class,
            'shift_type' => ShiftType::class,
        ];
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
}

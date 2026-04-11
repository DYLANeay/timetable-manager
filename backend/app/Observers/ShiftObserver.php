<?php

namespace App\Observers;

use App\Models\AppNotification;
use App\Models\Shift;

class ShiftObserver
{
    public function created(Shift $shift): void
    {
        if (! $shift->user_id) {
            return;
        }

        $shift->loadMissing('shiftTemplate');

        AppNotification::create([
            'user_id' => $shift->user_id,
            'type' => 'planning_updated',
            'data' => [
                'action' => 'created',
                'date' => $shift->date->format('Y-m-d'),
                'shift_type' => $shift->shiftTemplate->shift_type->value,
                'shift_id' => $shift->id,
            ],
        ]);
    }

    public function updated(Shift $shift): void
    {
        if (! $shift->wasChanged('user_id')) {
            return;
        }

        $shift->loadMissing('shiftTemplate');

        $originalUserId = $shift->getOriginal('user_id');
        $newUserId = $shift->user_id;

        if ($originalUserId) {
            AppNotification::create([
                'user_id' => $originalUserId,
                'type' => 'planning_updated',
                'data' => [
                    'action' => 'deleted',
                    'date' => $shift->date->format('Y-m-d'),
                    'shift_type' => $shift->shiftTemplate->shift_type->value,
                    'shift_id' => $shift->id,
                ],
            ]);
        }

        if ($newUserId) {
            AppNotification::create([
                'user_id' => $newUserId,
                'type' => 'planning_updated',
                'data' => [
                    'action' => 'created',
                    'date' => $shift->date->format('Y-m-d'),
                    'shift_type' => $shift->shiftTemplate->shift_type->value,
                    'shift_id' => $shift->id,
                ],
            ]);
        }
    }

    public function deleted(Shift $shift): void
    {
        if (! $shift->user_id) {
            return;
        }

        $shift->loadMissing('shiftTemplate');

        AppNotification::create([
            'user_id' => $shift->user_id,
            'type' => 'planning_updated',
            'data' => [
                'action' => 'deleted',
                'date' => $shift->date->format('Y-m-d'),
                'shift_type' => $shift->shiftTemplate->shift_type->value,
                'shift_id' => $shift->id,
            ],
        ]);
    }
}

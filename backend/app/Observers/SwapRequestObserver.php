<?php

namespace App\Observers;

use App\Enums\Role;
use App\Enums\SwapRequestStatus;
use App\Models\AppNotification;
use App\Models\SwapRequest;
use App\Models\User;

class SwapRequestObserver
{
    public function created(SwapRequest $swapRequest): void
    {
        $swapRequest->load(['requester', 'requesterShift.shiftTemplate', 'targetShift.shiftTemplate']);

        $managers = User::where('role', Role::Manager)->where('is_active', true)->get();

        foreach ($managers as $manager) {
            if ($manager->id === $swapRequest->requester_id) {
                continue;
            }

            AppNotification::create([
                'user_id' => $manager->id,
                'type' => 'swap_request',
                'data' => [
                    'requester_name' => $swapRequest->requester->name,
                    'swap_type' => $swapRequest->type,
                    'shift_date' => $swapRequest->requesterShift->date->format('Y-m-d'),
                    'shift_type' => $swapRequest->requesterShift->shiftTemplate->shift_type->value,
                    'swap_request_id' => $swapRequest->id,
                ],
            ]);
        }

        if ($swapRequest->type === 'swap' && $swapRequest->target_id) {
            AppNotification::create([
                'user_id' => $swapRequest->target_id,
                'type' => 'swap_targeting_you',
                'data' => [
                    'requester_name' => $swapRequest->requester->name,
                    'requester_shift_date' => $swapRequest->requesterShift->date->format('Y-m-d'),
                    'requester_shift_type' => $swapRequest->requesterShift->shiftTemplate->shift_type->value,
                    'your_shift_date' => $swapRequest->targetShift->date->format('Y-m-d'),
                    'your_shift_type' => $swapRequest->targetShift->shiftTemplate->shift_type->value,
                    'swap_request_id' => $swapRequest->id,
                ],
            ]);
        }
    }

    public function updated(SwapRequest $swapRequest): void
    {
        if (! $swapRequest->wasChanged('status')) {
            return;
        }

        $status = $swapRequest->status;

        if ($status !== SwapRequestStatus::ManagerApproved && $status !== SwapRequestStatus::ManagerDenied) {
            return;
        }

        $swapRequest->load(['manager', 'requesterShift.shiftTemplate', 'targetShift.shiftTemplate']);

        $decision = $status === SwapRequestStatus::ManagerApproved ? 'approved' : 'denied';
        $managerName = $swapRequest->manager?->name ?? 'Manager';

        AppNotification::create([
            'user_id' => $swapRequest->requester_id,
            'type' => 'swap_decided',
            'data' => [
                'decision' => $decision,
                'swap_type' => $swapRequest->type,
                'shift_date' => $swapRequest->requesterShift->date->format('Y-m-d'),
                'shift_type' => $swapRequest->requesterShift->shiftTemplate->shift_type->value,
                'decided_by' => $managerName,
                'swap_request_id' => $swapRequest->id,
            ],
        ]);

        if (
            $swapRequest->type === 'swap' &&
            $swapRequest->target_id &&
            $status === SwapRequestStatus::ManagerApproved
        ) {
            AppNotification::create([
                'user_id' => $swapRequest->target_id,
                'type' => 'swap_decided',
                'data' => [
                    'decision' => $decision,
                    'swap_type' => $swapRequest->type,
                    'shift_date' => $swapRequest->targetShift->date->format('Y-m-d'),
                    'shift_type' => $swapRequest->targetShift->shiftTemplate->shift_type->value,
                    'decided_by' => $managerName,
                    'swap_request_id' => $swapRequest->id,
                ],
            ]);
        }
    }
}

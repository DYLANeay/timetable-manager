<?php

namespace App\Observers;

use App\Enums\LeaveStatus;
use App\Enums\Role;
use App\Models\AppNotification;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\User;

class LeaveRequestObserver
{
    public function created(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->load('user');

        $hasHolidays = PublicHoliday::whereBetween('date', [
            $leaveRequest->start_date->format('Y-m-d'),
            $leaveRequest->end_date->format('Y-m-d'),
        ])->exists();

        $type = $hasHolidays ? 'holiday_request' : 'leave_request';

        $managers = User::where('role', Role::Manager)->where('is_active', true)->get();

        foreach ($managers as $manager) {
            AppNotification::create([
                'user_id' => $manager->id,
                'type' => $type,
                'data' => [
                    'requester_name' => $leaveRequest->user->name,
                    'start_date' => $leaveRequest->start_date->format('Y-m-d'),
                    'end_date' => $leaveRequest->end_date->format('Y-m-d'),
                    'leave_request_id' => $leaveRequest->id,
                ],
            ]);
        }
    }

    public function updated(LeaveRequest $leaveRequest): void
    {
        if (! $leaveRequest->wasChanged('status')) {
            return;
        }

        $status = $leaveRequest->status;

        if ($status !== LeaveStatus::Approved && $status !== LeaveStatus::Denied) {
            return;
        }

        $leaveRequest->load('manager');

        AppNotification::create([
            'user_id' => $leaveRequest->user_id,
            'type' => 'leave_decided',
            'data' => [
                'decision' => $status === LeaveStatus::Approved ? 'approved' : 'denied',
                'start_date' => $leaveRequest->start_date->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date->format('Y-m-d'),
                'decided_by' => $leaveRequest->manager?->name ?? 'Manager',
                'leave_request_id' => $leaveRequest->id,
            ],
        ]);
    }
}

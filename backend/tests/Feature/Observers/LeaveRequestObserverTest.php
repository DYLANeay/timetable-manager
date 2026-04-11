<?php

namespace Tests\Feature\Observers;

use App\Enums\LeaveStatus;
use App\Models\AppNotification;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use Tests\TestCase;

class LeaveRequestObserverTest extends TestCase
{
    // -------------------------------------------------------------------------
    // created()
    // -------------------------------------------------------------------------

    public function test_creating_leave_request_notifies_all_active_managers(): void
    {
        $manager1 = $this->createManager();
        $manager2 = $this->createManager();
        $emp      = $this->createEmployee();

        LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-05',
            'status'     => LeaveStatus::Pending,
        ]);

        $this->assertDatabaseHas('app_notifications', ['user_id' => $manager1->id, 'type' => 'leave_request']);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $manager2->id, 'type' => 'leave_request']);
    }

    public function test_leave_request_overlapping_public_holiday_uses_holiday_request_type(): void
    {
        $manager = $this->createManager();
        $emp     = $this->createEmployee();
        PublicHoliday::create(['date' => '2026-07-01', 'name' => 'Canada Day']);

        LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-06-29',  // spans the holiday
            'end_date'   => '2026-07-03',
            'status'     => LeaveStatus::Pending,
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $manager->id,
            'type'    => 'holiday_request',
        ]);
    }

    public function test_leave_request_without_holiday_overlap_uses_leave_request_type(): void
    {
        $manager = $this->createManager();
        $emp     = $this->createEmployee();

        LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-06',
            'end_date'   => '2026-07-10',
            'status'     => LeaveStatus::Pending,
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $manager->id,
            'type'    => 'leave_request',
        ]);
    }

    public function test_notification_data_contains_requester_name_and_dates(): void
    {
        $manager = $this->createManager();
        $emp     = $this->createEmployee(['name' => 'Alice Smith']);

        LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-03',
            'status'     => LeaveStatus::Pending,
        ]);

        $notif = AppNotification::where('user_id', $manager->id)->first();
        $this->assertEquals('Alice Smith', $notif->data['requester_name']);
        $this->assertEquals('2026-07-01', $notif->data['start_date']);
        $this->assertEquals('2026-07-03', $notif->data['end_date']);
    }

    public function test_inactive_managers_are_not_notified_for_leave_requests(): void
    {
        $active   = $this->createManager();
        $inactive = $this->createManager(['is_active' => false]);
        $emp      = $this->createEmployee();

        LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-03',
            'status'     => LeaveStatus::Pending,
        ]);

        $this->assertDatabaseHas('app_notifications', ['user_id' => $active->id]);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $inactive->id]);
    }

    // -------------------------------------------------------------------------
    // updated()
    // -------------------------------------------------------------------------

    public function test_leave_approval_notifies_the_employee_with_approved_decision(): void
    {
        $manager = $this->createManager();
        $emp     = $this->createEmployee();

        $leave = LeaveRequest::withoutEvents(fn () => LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-05',
            'status'     => LeaveStatus::Pending,
        ]));

        $leave->update([
            'status'     => LeaveStatus::Approved,
            'manager_id' => $manager->id,
            'decided_at' => now(),
        ]);

        $notif = AppNotification::where('user_id', $emp->id)->where('type', 'leave_decided')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('approved', $notif->data['decision']);
    }

    public function test_leave_denial_notifies_the_employee_with_denied_decision(): void
    {
        $manager = $this->createManager();
        $emp     = $this->createEmployee();

        $leave = LeaveRequest::withoutEvents(fn () => LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-05',
            'status'     => LeaveStatus::Pending,
        ]));

        $leave->update([
            'status'     => LeaveStatus::Denied,
            'manager_id' => $manager->id,
            'decided_at' => now(),
        ]);

        $notif = AppNotification::where('user_id', $emp->id)->where('type', 'leave_decided')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('denied', $notif->data['decision']);
    }

    public function test_updating_note_without_changing_status_does_not_trigger_notification(): void
    {
        $this->createManager();
        $emp = $this->createEmployee();

        $leave = LeaveRequest::withoutEvents(fn () => LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-05',
            'status'     => LeaveStatus::Pending,
        ]));

        // Update only the note — status remains Pending, no notification expected
        $leave->update(['note' => 'Holiday plans']);

        $this->assertDatabaseMissing('app_notifications', ['user_id' => $emp->id, 'type' => 'leave_decided']);
    }
}

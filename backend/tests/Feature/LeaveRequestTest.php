<?php

namespace Tests\Feature;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    private function makePendingLeave(int $userId, string $start = '2026-07-01', string $end = '2026-07-05'): LeaveRequest
    {
        return LeaveRequest::withoutEvents(fn () => LeaveRequest::create([
            'user_id'    => $userId,
            'start_date' => $start,
            'end_date'   => $end,
            'status'     => LeaveStatus::Pending,
        ]));
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_employee_can_create_leave_request(): void
    {
        $this->asEmployee();

        $this->postJson('/api/leave-requests', [
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-05',
        ])->assertStatus(201)
            ->assertJsonFragment(['status' => 'pending']);
    }

    public function test_leave_request_start_date_must_be_today_or_later(): void
    {
        $this->asEmployee();

        $this->postJson('/api/leave-requests', [
            'start_date' => '2020-01-01',
            'end_date'   => '2020-01-05',
        ])->assertStatus(422)->assertJsonValidationErrors(['start_date']);
    }

    public function test_leave_request_end_date_must_not_be_before_start_date(): void
    {
        $this->asEmployee();

        $this->postJson('/api/leave-requests', [
            'start_date' => '2026-07-10',
            'end_date'   => '2026-07-05',
        ])->assertStatus(422)->assertJsonValidationErrors(['end_date']);
    }

    public function test_leave_request_note_is_optional(): void
    {
        $this->asEmployee();

        $this->postJson('/api/leave-requests', [
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-01',
        ])->assertStatus(201);
    }

    public function test_leave_request_note_cannot_exceed_500_characters(): void
    {
        $this->asEmployee();

        $this->postJson('/api/leave-requests', [
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-01',
            'note'       => str_repeat('x', 501),
        ])->assertStatus(422)->assertJsonValidationErrors(['note']);
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_employee_can_view_their_own_leave_requests(): void
    {
        $emp = $this->asEmployee();
        $this->makePendingLeave($emp->id);

        $this->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_manager_can_view_all_leave_requests(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $this->makePendingLeave($emp1->id, '2026-07-01', '2026-07-03');
        $this->makePendingLeave($emp2->id, '2026-07-07', '2026-07-09');

        $this->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_leave_requests_can_be_filtered_by_year(): void
    {
        $emp = $this->asEmployee();
        $this->makePendingLeave($emp->id, '2025-12-29', '2026-01-02');

        // Spans both years — appears in both
        $this->getJson('/api/leave-requests?year=2025')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/leave-requests?year=2026')->assertOk()->assertJsonCount(1, 'data');
        // Should NOT appear for a different year
        $this->getJson('/api/leave-requests?year=2024')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_response_includes_computed_business_days(): void
    {
        $emp = $this->asEmployee();
        // Mon–Fri 2026-06-01 to 2026-06-05 = 5 business days
        $this->makePendingLeave($emp->id, '2026-06-01', '2026-06-05');

        $this->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonFragment(['business_days' => 5]);
    }

    // -------------------------------------------------------------------------
    // Decide (manager only)
    // -------------------------------------------------------------------------

    public function test_manager_can_approve_leave_request(): void
    {
        $this->asManager();
        $emp   = $this->createEmployee();
        $leave = $this->makePendingLeave($emp->id);

        $this->putJson("/api/leave-requests/{$leave->id}/decide", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);
    }

    public function test_manager_can_deny_leave_request(): void
    {
        $this->asManager();
        $emp   = $this->createEmployee();
        $leave = $this->makePendingLeave($emp->id);

        $this->putJson("/api/leave-requests/{$leave->id}/decide", ['status' => 'denied'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'denied']);
    }

    public function test_decide_records_manager_and_timestamp(): void
    {
        $manager = $this->asManager();
        $emp     = $this->createEmployee();
        $leave   = $this->makePendingLeave($emp->id);

        $this->putJson("/api/leave-requests/{$leave->id}/decide", ['status' => 'approved']);

        $this->assertDatabaseHas('leave_requests', [
            'id'         => $leave->id,
            'manager_id' => $manager->id,
        ]);
        $this->assertNotNull($leave->fresh()->decided_at);
    }

    public function test_decide_requires_valid_status_value(): void
    {
        $this->asManager();
        $emp   = $this->createEmployee();
        $leave = $this->makePendingLeave($emp->id);

        $this->putJson("/api/leave-requests/{$leave->id}/decide", ['status' => 'maybe'])
            ->assertStatus(422);
    }

    public function test_employee_cannot_decide_leave_request(): void
    {
        $this->asEmployee();
        $emp2  = $this->createEmployee();
        $leave = $this->makePendingLeave($emp2->id);

        $this->putJson("/api/leave-requests/{$leave->id}/decide", ['status' => 'approved'])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    public function test_employee_can_cancel_their_own_pending_request(): void
    {
        $emp   = $this->asEmployee();
        $leave = $this->makePendingLeave($emp->id);

        $this->deleteJson("/api/leave-requests/{$leave->id}")->assertNoContent();
        $this->assertDatabaseMissing('leave_requests', ['id' => $leave->id]);
    }

    public function test_employee_cannot_cancel_an_approved_request(): void
    {
        $emp   = $this->asEmployee();
        $leave = LeaveRequest::withoutEvents(fn () => LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-05',
            'status'     => LeaveStatus::Approved,
        ]));

        $this->deleteJson("/api/leave-requests/{$leave->id}")->assertStatus(422);
    }

    public function test_employee_cannot_cancel_another_employees_request(): void
    {
        $this->asEmployee();
        $other = $this->createEmployee();
        $leave = $this->makePendingLeave($other->id);

        $this->deleteJson("/api/leave-requests/{$leave->id}")->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Balance
    // -------------------------------------------------------------------------

    public function test_leave_balance_total_is_25_days(): void
    {
        $this->asEmployee();

        $this->getJson('/api/leave-requests/balance')
            ->assertOk()
            ->assertJsonFragment(['total_days' => 25]);
    }

    public function test_leave_balance_starts_at_zero_used_days(): void
    {
        $this->asEmployee();

        $this->getJson('/api/leave-requests/balance')
            ->assertOk()
            ->assertJsonFragment(['used_days' => 0, 'remaining_days' => 25]);
    }

    public function test_balance_includes_only_approved_requests(): void
    {
        $emp = $this->asEmployee();

        // Pending — should NOT count
        $this->makePendingLeave($emp->id, '2026-07-07', '2026-07-11');

        // Approved Mon–Fri 2026-07-13 to 2026-07-17 = 5 business days (Mon–Fri)
        LeaveRequest::withoutEvents(fn () => LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-13',
            'end_date'   => '2026-07-17',
            'status'     => LeaveStatus::Approved,
        ]));

        $this->getJson('/api/leave-requests/balance')
            ->assertOk()
            ->assertJsonFragment(['used_days' => 5, 'remaining_days' => 20]);
    }

    public function test_balance_counts_only_business_days_not_weekends(): void
    {
        $emp = $this->asEmployee();

        // Mon–Sun 2026-06-01 to 2026-06-07 = 7 calendar, 5 business days
        LeaveRequest::withoutEvents(fn () => LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-07',
            'status'     => LeaveStatus::Approved,
        ]));

        $this->getJson('/api/leave-requests/balance')
            ->assertOk()
            ->assertJsonFragment(['used_days' => 5]);
    }

    public function test_manager_can_view_balance_for_specific_employee(): void
    {
        $this->asManager();
        $emp = $this->createEmployee();

        LeaveRequest::withoutEvents(fn () => LeaveRequest::create([
            'user_id'    => $emp->id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-02',
            'status'     => LeaveStatus::Approved,
        ]));

        $this->getJson("/api/leave-requests/balance?user_id={$emp->id}")
            ->assertOk()
            ->assertJsonFragment(['used_days' => 2]);
    }
}

<?php

namespace Tests\Feature\Observers;

use App\Enums\SwapRequestStatus;
use App\Models\AppNotification;
use App\Models\Shift;
use App\Models\SwapRequest;
use Tests\TestCase;

class SwapRequestObserverTest extends TestCase
{
    // -------------------------------------------------------------------------
    // created()
    // -------------------------------------------------------------------------

    public function test_creating_swap_request_notifies_all_active_managers(): void
    {
        $manager1 = $this->createManager();
        $manager2 = $this->createManager();
        $emp1     = $this->createEmployee();
        $emp2     = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));

        SwapRequest::create([
            'requester_id'       => $emp1->id,
            'target_id'          => $emp2->id,
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
            'type'               => 'swap',
            'status'             => SwapRequestStatus::PendingPeer,
        ]);

        $this->assertDatabaseHas('app_notifications', ['user_id' => $manager1->id, 'type' => 'swap_request']);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $manager2->id, 'type' => 'swap_request']);
    }

    public function test_creating_swap_request_notifies_target_employee_with_swap_targeting_you(): void
    {
        $this->createManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));

        SwapRequest::create([
            'requester_id'       => $emp1->id,
            'target_id'          => $emp2->id,
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
            'type'               => 'swap',
            'status'             => SwapRequestStatus::PendingPeer,
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $emp2->id,
            'type'    => 'swap_targeting_you',
        ]);
    }

    public function test_creating_giveaway_notifies_managers_but_no_swap_targeting_you(): void
    {
        $manager = $this->createManager();
        $emp     = $this->createEmployee();
        $shift   = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-01'));

        SwapRequest::create([
            'requester_id'       => $emp->id,
            'requester_shift_id' => $shift->id,
            'type'               => 'giveaway',
            'status'             => SwapRequestStatus::Open,
        ]);

        $this->assertDatabaseHas('app_notifications', ['user_id' => $manager->id, 'type' => 'swap_request']);
        $this->assertDatabaseMissing('app_notifications', ['type' => 'swap_targeting_you']);
    }

    public function test_manager_who_is_requester_is_not_self_notified(): void
    {
        $managerRequester = $this->createManager();
        $otherManager     = $this->createManager();
        $emp              = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($managerRequester, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-02'));

        SwapRequest::create([
            'requester_id'       => $managerRequester->id,
            'target_id'          => $emp->id,
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
            'type'               => 'swap',
            'status'             => SwapRequestStatus::PendingPeer,
        ]);

        $this->assertDatabaseHas('app_notifications', ['user_id' => $otherManager->id, 'type' => 'swap_request']);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $managerRequester->id, 'type' => 'swap_request']);
    }

    public function test_inactive_managers_are_not_notified_for_swap_requests(): void
    {
        $active   = $this->createManager();
        $inactive = $this->createManager(['is_active' => false]);
        $emp1     = $this->createEmployee();
        $emp2     = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));

        SwapRequest::create([
            'requester_id'       => $emp1->id,
            'target_id'          => $emp2->id,
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
            'type'               => 'swap',
            'status'             => SwapRequestStatus::PendingPeer,
        ]);

        $this->assertDatabaseHas('app_notifications', ['user_id' => $active->id]);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $inactive->id]);
    }

    // -------------------------------------------------------------------------
    // updated()
    // -------------------------------------------------------------------------

    public function test_swap_approval_notifies_requester_with_approved_decision(): void
    {
        $manager = $this->createManager();
        $emp1    = $this->createEmployee();
        $emp2    = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));

        $swap = SwapRequest::withoutEvents(fn () => SwapRequest::create([
            'requester_id'       => $emp1->id,
            'target_id'          => $emp2->id,
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
            'type'               => 'swap',
            'status'             => SwapRequestStatus::PeerAccepted,
        ]));

        $swap->update([
            'status'             => SwapRequestStatus::ManagerApproved,
            'manager_id'         => $manager->id,
            'manager_decided_at' => now(),
        ]);

        $notif = AppNotification::where('user_id', $emp1->id)->where('type', 'swap_decided')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('approved', $notif->data['decision']);
    }

    public function test_swap_approval_also_notifies_target_employee(): void
    {
        $manager = $this->createManager();
        $emp1    = $this->createEmployee();
        $emp2    = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));

        $swap = SwapRequest::withoutEvents(fn () => SwapRequest::create([
            'requester_id'       => $emp1->id,
            'target_id'          => $emp2->id,
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
            'type'               => 'swap',
            'status'             => SwapRequestStatus::PeerAccepted,
        ]));

        $swap->update([
            'status'             => SwapRequestStatus::ManagerApproved,
            'manager_id'         => $manager->id,
            'manager_decided_at' => now(),
        ]);

        $this->assertDatabaseHas('app_notifications', ['user_id' => $emp2->id, 'type' => 'swap_decided']);
    }

    public function test_swap_denial_only_notifies_requester_not_target(): void
    {
        $manager = $this->createManager();
        $emp1    = $this->createEmployee();
        $emp2    = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));

        $swap = SwapRequest::withoutEvents(fn () => SwapRequest::create([
            'requester_id'       => $emp1->id,
            'target_id'          => $emp2->id,
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
            'type'               => 'swap',
            'status'             => SwapRequestStatus::PeerAccepted,
        ]));

        $swap->update([
            'status'             => SwapRequestStatus::ManagerDenied,
            'manager_id'         => $manager->id,
            'manager_decided_at' => now(),
        ]);

        $this->assertDatabaseHas('app_notifications', ['user_id' => $emp1->id, 'type' => 'swap_decided']);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $emp2->id, 'type' => 'swap_decided']);
    }

    public function test_giveaway_approval_notifies_owner_with_approved_decision(): void
    {
        $manager = $this->createManager();
        $owner   = $this->createEmployee();
        $claimer = $this->createEmployee();
        $shift   = Shift::withoutEvents(fn () => $this->makeShift($owner, '2026-06-01'));

        $giveaway = SwapRequest::withoutEvents(fn () => SwapRequest::create([
            'requester_id'       => $owner->id,
            'target_id'          => $claimer->id,
            'requester_shift_id' => $shift->id,
            'type'               => 'giveaway',
            'status'             => SwapRequestStatus::PeerAccepted,
        ]));

        $giveaway->update([
            'status'             => SwapRequestStatus::ManagerApproved,
            'manager_id'         => $manager->id,
            'manager_decided_at' => now(),
        ]);

        $notif = AppNotification::where('user_id', $owner->id)->where('type', 'swap_decided')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('approved', $notif->data['decision']);
    }

    public function test_peer_accept_status_does_not_trigger_decided_notification(): void
    {
        $this->createManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));

        $swap = SwapRequest::withoutEvents(fn () => SwapRequest::create([
            'requester_id'       => $emp1->id,
            'target_id'          => $emp2->id,
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
            'type'               => 'swap',
            'status'             => SwapRequestStatus::PendingPeer,
        ]));

        // Transition to PeerAccepted — should NOT create a swap_decided notification
        $swap->update(['status' => SwapRequestStatus::PeerAccepted, 'peer_responded_at' => now()]);

        $this->assertDatabaseMissing('app_notifications', ['type' => 'swap_decided']);
    }
}

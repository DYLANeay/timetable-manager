<?php

namespace Tests\Feature;

use App\Enums\SwapRequestStatus;
use App\Models\Shift;
use App\Models\SwapRequest;
use Tests\TestCase;

class SwapRequestTest extends TestCase
{
    /** Create a swap request directly in the DB (bypassing observers) */
    private function makeSwap(
        int $requesterId,
        int $targetId,
        int $requesterShiftId,
        int $targetShiftId,
        SwapRequestStatus $status = SwapRequestStatus::PendingPeer,
    ): SwapRequest {
        return SwapRequest::withoutEvents(fn () => SwapRequest::create([
            'requester_id'       => $requesterId,
            'target_id'          => $targetId,
            'requester_shift_id' => $requesterShiftId,
            'target_shift_id'    => $targetShiftId,
            'type'               => 'swap',
            'status'             => $status,
        ]));
    }

    /** Create a giveaway directly in the DB (bypassing observers) */
    private function makeGiveaway(
        int $requesterId,
        int $requesterShiftId,
        SwapRequestStatus $status = SwapRequestStatus::Open,
        ?int $targetId = null,
    ): SwapRequest {
        return SwapRequest::withoutEvents(fn () => SwapRequest::create([
            'requester_id'       => $requesterId,
            'target_id'          => $targetId,
            'requester_shift_id' => $requesterShiftId,
            'type'               => 'giveaway',
            'status'             => $status,
        ]));
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_manager_sees_all_swap_requests(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id);

        $this->getJson('/api/swap-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_employee_only_sees_own_and_open_giveaway_requests(): void
    {
        $emp    = $this->asEmployee();
        $other1 = $this->createEmployee();
        $other2 = $this->createEmployee();

        $myShift     = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-01'));
        $other1Shift = Shift::withoutEvents(fn () => $this->makeShift($other1, '2026-06-02'));
        $other2Shift = Shift::withoutEvents(fn () => $this->makeShift($other2, '2026-06-03'));

        // Targets emp → visible
        $this->makeSwap($other1->id, $emp->id, $other1Shift->id, $myShift->id);
        // Open giveaway from other2 → visible (anyone can see open giveaways)
        $this->makeGiveaway($other2->id, $other2Shift->id);
        // Between two others, not involving emp → NOT visible
        $this->makeSwap($other1->id, $other2->id, $other1Shift->id, $other2Shift->id);

        $this->getJson('/api/swap-requests')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // -------------------------------------------------------------------------
    // Create swap
    // -------------------------------------------------------------------------

    public function test_employee_can_create_swap_request(): void
    {
        $emp    = $this->asEmployee();
        $target = $this->createEmployee();
        $myShift     = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-01'));
        $targetShift = Shift::withoutEvents(fn () => $this->makeShift($target, '2026-06-02'));

        $this->postJson('/api/swap-requests', [
            'type'               => 'swap',
            'requester_shift_id' => $myShift->id,
            'target_shift_id'    => $targetShift->id,
        ])->assertStatus(201)
            ->assertJsonPath('data.type', 'swap')
            ->assertJsonPath('data.status', 'pending_peer');
    }

    public function test_employee_cannot_offer_a_shift_they_dont_own(): void
    {
        $this->asEmployee();
        $other       = $this->createEmployee();
        $third       = $this->createEmployee();
        $otherShift  = Shift::withoutEvents(fn () => $this->makeShift($other, '2026-06-01'));
        $targetShift = Shift::withoutEvents(fn () => $this->makeShift($third, '2026-06-02'));

        $this->postJson('/api/swap-requests', [
            'type'               => 'swap',
            'requester_shift_id' => $otherShift->id,
            'target_shift_id'    => $targetShift->id,
        ])->assertForbidden();
    }

    public function test_employee_cannot_swap_with_themselves(): void
    {
        $emp = $this->asEmployee();
        $s1  = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-01'));
        $s2  = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-02'));

        $this->postJson('/api/swap-requests', [
            'type'               => 'swap',
            'requester_shift_id' => $s1->id,
            'target_shift_id'    => $s2->id,
        ])->assertStatus(422);
    }

    public function test_cannot_swap_with_an_unassigned_shift(): void
    {
        $emp     = $this->asEmployee();
        $myShift = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-01'));
        $template = $this->makeTemplate();
        $emptyShift = Shift::withoutEvents(fn () => Shift::create([
            'shift_template_id' => $template->id,
            'date'              => '2026-06-02',
        ]));

        $this->postJson('/api/swap-requests', [
            'type'               => 'swap',
            'requester_shift_id' => $myShift->id,
            'target_shift_id'    => $emptyShift->id,
        ])->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Create giveaway
    // -------------------------------------------------------------------------

    public function test_employee_can_create_giveaway(): void
    {
        $emp   = $this->asEmployee();
        $shift = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-01'));

        $this->postJson('/api/swap-requests', [
            'type'               => 'giveaway',
            'requester_shift_id' => $shift->id,
        ])->assertStatus(201)
            ->assertJsonPath('data.type', 'giveaway')
            ->assertJsonPath('data.status', 'open');
    }

    public function test_cannot_create_duplicate_giveaway_for_same_shift(): void
    {
        $emp   = $this->asEmployee();
        $shift = Shift::withoutEvents(fn () => $this->makeShift($emp, '2026-06-01'));

        $this->postJson('/api/swap-requests', ['type' => 'giveaway', 'requester_shift_id' => $shift->id])->assertStatus(201);
        $this->postJson('/api/swap-requests', ['type' => 'giveaway', 'requester_shift_id' => $shift->id])->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Respond to swap
    // -------------------------------------------------------------------------

    public function test_target_employee_can_accept_swap_request(): void
    {
        $requester = $this->createEmployee();
        $target    = $this->asEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($requester, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($target, '2026-06-02'));
        $swap = $this->makeSwap($requester->id, $target->id, $s1->id, $s2->id);

        $this->putJson("/api/swap-requests/{$swap->id}/respond", ['accept' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'peer_accepted');
    }

    public function test_target_employee_can_decline_swap_request(): void
    {
        $requester = $this->createEmployee();
        $target    = $this->asEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($requester, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($target, '2026-06-02'));
        $swap = $this->makeSwap($requester->id, $target->id, $s1->id, $s2->id);

        $this->putJson("/api/swap-requests/{$swap->id}/respond", ['accept' => false])
            ->assertOk()
            ->assertJsonPath('data.status', 'peer_declined');
    }

    public function test_non_target_employee_cannot_respond_to_swap(): void
    {
        $requester   = $this->createEmployee();
        $target      = $this->createEmployee();
        $this->asEmployee(); // third party
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($requester, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($target, '2026-06-02'));
        $swap = $this->makeSwap($requester->id, $target->id, $s1->id, $s2->id);

        $this->putJson("/api/swap-requests/{$swap->id}/respond", ['accept' => true])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Claim giveaway
    // -------------------------------------------------------------------------

    public function test_employee_can_claim_an_open_giveaway(): void
    {
        $owner    = $this->createEmployee();
        $this->asEmployee(); // claimer
        $shift    = Shift::withoutEvents(fn () => $this->makeShift($owner, '2026-06-01'));
        $giveaway = $this->makeGiveaway($owner->id, $shift->id);

        $this->putJson("/api/swap-requests/{$giveaway->id}/claim")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_peer');
    }

    public function test_owner_cannot_claim_their_own_giveaway(): void
    {
        $owner    = $this->asEmployee();
        $shift    = Shift::withoutEvents(fn () => $this->makeShift($owner, '2026-06-01'));
        $giveaway = $this->makeGiveaway($owner->id, $shift->id);

        $this->putJson("/api/swap-requests/{$giveaway->id}/claim")->assertStatus(422);
    }

    public function test_already_claimed_giveaway_cannot_be_claimed_again(): void
    {
        $owner   = $this->createEmployee();
        $claimer = $this->createEmployee();
        $this->asEmployee(); // third party
        $shift    = Shift::withoutEvents(fn () => $this->makeShift($owner, '2026-06-01'));
        $giveaway = $this->makeGiveaway($owner->id, $shift->id, SwapRequestStatus::PendingPeer, $claimer->id);

        $this->putJson("/api/swap-requests/{$giveaway->id}/claim")->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Giveaway owner confirms / rejects claimer
    // -------------------------------------------------------------------------

    public function test_giveaway_owner_can_confirm_claimer(): void
    {
        $owner   = $this->asEmployee();
        $claimer = $this->createEmployee();
        $shift    = Shift::withoutEvents(fn () => $this->makeShift($owner, '2026-06-01'));
        $giveaway = $this->makeGiveaway($owner->id, $shift->id, SwapRequestStatus::PendingPeer, $claimer->id);

        $this->putJson("/api/swap-requests/{$giveaway->id}/respond", ['accept' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'peer_accepted');
    }

    public function test_giveaway_owner_can_reject_claimer_and_put_back_to_open(): void
    {
        $owner   = $this->asEmployee();
        $claimer = $this->createEmployee();
        $shift    = Shift::withoutEvents(fn () => $this->makeShift($owner, '2026-06-01'));
        $giveaway = $this->makeGiveaway($owner->id, $shift->id, SwapRequestStatus::PendingPeer, $claimer->id);

        $this->putJson("/api/swap-requests/{$giveaway->id}/respond", ['accept' => false])
            ->assertOk()
            ->assertJsonPath('data.status', 'open');
    }

    // -------------------------------------------------------------------------
    // Manager decides
    // -------------------------------------------------------------------------

    public function test_manager_can_approve_swap_request(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id, SwapRequestStatus::PeerAccepted);

        $this->putJson("/api/swap-requests/{$swap->id}/decide", ['approve' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'manager_approved');
    }

    public function test_manager_can_deny_swap_request(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id, SwapRequestStatus::PeerAccepted);

        $this->putJson("/api/swap-requests/{$swap->id}/decide", ['approve' => false])
            ->assertOk()
            ->assertJsonPath('data.status', 'manager_denied');
    }

    public function test_approve_swap_exchanges_shift_assignments(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id, SwapRequestStatus::PeerAccepted);

        $this->putJson("/api/swap-requests/{$swap->id}/decide", ['approve' => true]);

        $this->assertDatabaseHas('shifts', ['id' => $s1->id, 'user_id' => $emp2->id]);
        $this->assertDatabaseHas('shifts', ['id' => $s2->id, 'user_id' => $emp1->id]);
    }

    public function test_approve_giveaway_transfers_shift_to_claimer(): void
    {
        $this->asManager();
        $owner   = $this->createEmployee();
        $claimer = $this->createEmployee();
        $shift    = Shift::withoutEvents(fn () => $this->makeShift($owner, '2026-06-01'));
        $giveaway = $this->makeGiveaway($owner->id, $shift->id, SwapRequestStatus::PeerAccepted, $claimer->id);

        $this->putJson("/api/swap-requests/{$giveaway->id}/decide", ['approve' => true]);

        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'user_id' => $claimer->id]);
    }

    public function test_deny_swap_does_not_change_shift_assignments(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id, SwapRequestStatus::PeerAccepted);

        $this->putJson("/api/swap-requests/{$swap->id}/decide", ['approve' => false]);

        $this->assertDatabaseHas('shifts', ['id' => $s1->id, 'user_id' => $emp1->id]);
        $this->assertDatabaseHas('shifts', ['id' => $s2->id, 'user_id' => $emp2->id]);
    }

    public function test_decide_requires_peer_accepted_status(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        // Still PendingPeer — not yet accepted
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id, SwapRequestStatus::PendingPeer);

        $this->putJson("/api/swap-requests/{$swap->id}/decide", ['approve' => true])
            ->assertStatus(422);
    }

    public function test_employee_cannot_decide_swap(): void
    {
        $emp1 = $this->asEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id, SwapRequestStatus::PeerAccepted);

        $this->putJson("/api/swap-requests/{$swap->id}/decide", ['approve' => true])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    public function test_requester_can_cancel_their_pending_swap_request(): void
    {
        $emp1 = $this->asEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id);

        $this->putJson("/api/swap-requests/{$swap->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_non_requester_cannot_cancel_swap_request(): void
    {
        $emp1 = $this->createEmployee();
        $emp2 = $this->asEmployee(); // emp2 is authenticated but not the requester
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id);

        $this->putJson("/api/swap-requests/{$swap->id}/cancel")->assertForbidden();
    }

    public function test_cannot_cancel_an_already_approved_swap(): void
    {
        $emp1 = $this->asEmployee();
        $emp2 = $this->createEmployee();
        $s1   = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2   = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));
        $swap = $this->makeSwap($emp1->id, $emp2->id, $s1->id, $s2->id, SwapRequestStatus::ManagerApproved);

        $this->putJson("/api/swap-requests/{$swap->id}/cancel")->assertStatus(422);
    }
}

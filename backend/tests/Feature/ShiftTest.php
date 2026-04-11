<?php

namespace Tests\Feature;

use App\Models\Shift;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_fetch_shifts_by_week(): void
    {
        $this->asEmployee();
        $template = $this->makeTemplate();

        // Bypass observer (no user_id, but use withoutEvents to be safe)
        Shift::withoutEvents(fn () => Shift::create(['shift_template_id' => $template->id, 'date' => '2026-06-01']));
        Shift::withoutEvents(fn () => Shift::create(['shift_template_id' => $template->id, 'date' => '2026-06-08']));

        $response = $this->getJson('/api/shifts?week=2026-06-01')->assertOk();

        $dates = collect($response->json('data'))->pluck('date');
        $this->assertTrue($dates->contains('2026-06-01'));
        $this->assertFalse($dates->contains('2026-06-08'));
    }

    public function test_authenticated_user_can_fetch_shifts_by_month(): void
    {
        $this->asEmployee();
        $template = $this->makeTemplate();

        Shift::withoutEvents(fn () => Shift::create(['shift_template_id' => $template->id, 'date' => '2026-06-15']));
        Shift::withoutEvents(fn () => Shift::create(['shift_template_id' => $template->id, 'date' => '2026-07-01']));

        $response = $this->getJson('/api/shifts?month=2026-06')->assertOk();

        $dates = collect($response->json('data'))->pluck('date');
        $this->assertTrue($dates->contains('2026-06-15'));
        $this->assertFalse($dates->contains('2026-07-01'));
    }

    public function test_shift_index_requires_week_or_month(): void
    {
        $this->asEmployee();

        $this->getJson('/api/shifts')->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_fetch_shifts(): void
    {
        $this->getJson('/api/shifts?week=2026-06-01')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // My shifts
    // -------------------------------------------------------------------------

    public function test_employee_can_get_their_own_shifts_for_a_week(): void
    {
        $emp      = $this->asEmployee();
        $other    = $this->createEmployee();
        $template = $this->makeTemplate();

        Shift::withoutEvents(fn () => Shift::create(['user_id' => $emp->id, 'shift_template_id' => $template->id, 'date' => '2026-06-01']));
        Shift::withoutEvents(fn () => Shift::create(['user_id' => $other->id, 'shift_template_id' => $template->id, 'date' => '2026-06-02']));

        $response = $this->getJson('/api/shifts/my?week=2026-06-01')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('2026-06-01', $response->json('data.0.date'));
    }

    public function test_my_shifts_requires_week_parameter(): void
    {
        $this->asEmployee();

        $this->getJson('/api/shifts/my')->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Create (manager only)
    // -------------------------------------------------------------------------

    public function test_manager_can_create_shift_without_user(): void
    {
        $this->asManager();
        $template = $this->makeTemplate();

        $this->postJson('/api/shifts', [
            'shift_template_id' => $template->id,
            'date'              => '2026-06-10',
        ])->assertStatus(201)->assertJsonFragment(['date' => '2026-06-10']);
    }

    public function test_manager_can_create_shift_assigned_to_employee(): void
    {
        $this->asManager();
        $emp      = $this->createEmployee();
        $template = $this->makeTemplate();

        $response = $this->postJson('/api/shifts', [
            'user_id'           => $emp->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-06-10',
        ])->assertStatus(201);

        $this->assertEquals($emp->id, $response->json('data.user.id'));
    }

    public function test_employee_cannot_create_shift(): void
    {
        $this->asEmployee();
        $template = $this->makeTemplate();

        $this->postJson('/api/shifts', [
            'shift_template_id' => $template->id,
            'date'              => '2026-06-10',
        ])->assertForbidden();
    }

    public function test_create_shift_requires_valid_template(): void
    {
        $this->asManager();

        $this->postJson('/api/shifts', [
            'shift_template_id' => 9999,
            'date'              => '2026-06-10',
        ])->assertStatus(422)->assertJsonValidationErrors(['shift_template_id']);
    }

    public function test_create_shift_requires_valid_user_when_provided(): void
    {
        $this->asManager();
        $template = $this->makeTemplate();

        $this->postJson('/api/shifts', [
            'user_id'           => 9999,
            'shift_template_id' => $template->id,
            'date'              => '2026-06-10',
        ])->assertStatus(422)->assertJsonValidationErrors(['user_id']);
    }

    // -------------------------------------------------------------------------
    // Update (manager only)
    // -------------------------------------------------------------------------

    public function test_manager_can_reassign_shift_to_different_employee(): void
    {
        $this->asManager();
        $emp1  = $this->createEmployee();
        $emp2  = $this->createEmployee();
        $shift = Shift::withoutEvents(fn () => $this->makeShift($emp1));

        $this->putJson("/api/shifts/{$shift->id}", ['user_id' => $emp2->id])
            ->assertOk()
            ->assertJsonFragment(['id' => $emp2->id]);
    }

    public function test_manager_can_unassign_shift(): void
    {
        $this->asManager();
        $emp   = $this->createEmployee();
        $shift = Shift::withoutEvents(fn () => $this->makeShift($emp));

        $this->putJson("/api/shifts/{$shift->id}", ['user_id' => null])
            ->assertOk()
            ->assertJsonFragment(['user' => null]);
    }

    public function test_employee_cannot_update_shift(): void
    {
        $this->asEmployee();
        $template = $this->makeTemplate();
        $shift    = Shift::withoutEvents(fn () => Shift::create(['shift_template_id' => $template->id, 'date' => '2026-06-10']));

        $this->putJson("/api/shifts/{$shift->id}", ['notes' => 'hello'])->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Delete (manager only)
    // -------------------------------------------------------------------------

    public function test_manager_can_delete_shift(): void
    {
        $this->asManager();
        $emp   = $this->createEmployee();
        $shift = Shift::withoutEvents(fn () => $this->makeShift($emp));

        $this->deleteJson("/api/shifts/{$shift->id}")->assertNoContent();
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_employee_cannot_delete_shift(): void
    {
        $this->asEmployee();
        $template = $this->makeTemplate();
        $shift    = Shift::withoutEvents(fn () => Shift::create([
            'shift_template_id' => $template->id,
            'date'              => '2026-06-10',
        ]));

        $this->deleteJson("/api/shifts/{$shift->id}")->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Bulk create (manager only)
    // -------------------------------------------------------------------------

    public function test_manager_can_bulk_create_shifts(): void
    {
        $this->asManager();
        $template = $this->makeTemplate();
        $emp      = $this->createEmployee();

        $response = $this->postJson('/api/shifts/bulk', [
            'shifts' => [
                ['user_id' => $emp->id, 'shift_template_id' => $template->id, 'date' => '2026-06-01'],
                ['user_id' => $emp->id, 'shift_template_id' => $template->id, 'date' => '2026-06-02'],
            ],
        ])->assertStatus(201);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_bulk_create_updates_existing_shift_for_same_date_and_template(): void
    {
        $this->asManager();
        $template = $this->makeTemplate();
        $emp1     = $this->createEmployee();
        $emp2     = $this->createEmployee();

        // Pre-existing shift
        Shift::withoutEvents(fn () => Shift::create([
            'user_id'           => $emp1->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]));

        $response = $this->postJson('/api/shifts/bulk', [
            'shifts' => [
                ['user_id' => $emp2->id, 'shift_template_id' => $template->id, 'date' => '2026-06-01'],
            ],
        ])->assertStatus(201);

        // Response returns exactly the upserted shift assigned to emp2
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($emp2->id, $response->json('data.0.user.id'));
        $this->assertDatabaseHas('shifts', ['user_id' => $emp2->id, 'shift_template_id' => $template->id]);
    }

    public function test_employee_cannot_bulk_create_shifts(): void
    {
        $this->asEmployee();
        $template = $this->makeTemplate();

        $this->postJson('/api/shifts/bulk', [
            'shifts' => [['shift_template_id' => $template->id, 'date' => '2026-06-01']],
        ])->assertForbidden();
    }
}

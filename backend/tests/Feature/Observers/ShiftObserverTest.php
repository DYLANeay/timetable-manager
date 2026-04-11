<?php

namespace Tests\Feature\Observers;

use App\Models\AppNotification;
use App\Models\Shift;
use Tests\TestCase;

class ShiftObserverTest extends TestCase
{
    // -------------------------------------------------------------------------
    // created()
    // -------------------------------------------------------------------------

    public function test_creating_assigned_shift_notifies_the_assigned_user(): void
    {
        $emp      = $this->createEmployee();
        $template = $this->makeTemplate();

        Shift::create([
            'user_id'           => $emp->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]);

        $notif = AppNotification::where('user_id', $emp->id)->where('type', 'planning_updated')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('created', $notif->data['action']);
        $this->assertEquals('2026-06-01', $notif->data['date']);
    }

    public function test_creating_unassigned_shift_does_not_trigger_notification(): void
    {
        $template = $this->makeTemplate();

        Shift::create([
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]);

        $this->assertDatabaseMissing('app_notifications', ['type' => 'planning_updated']);
    }

    // -------------------------------------------------------------------------
    // updated()
    // -------------------------------------------------------------------------

    public function test_assigning_user_to_previously_unassigned_shift_notifies_new_user(): void
    {
        $emp      = $this->createEmployee();
        $template = $this->makeTemplate();

        $shift = Shift::withoutEvents(fn () => Shift::create([
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]));

        $shift->update(['user_id' => $emp->id]);

        $notif = AppNotification::where('user_id', $emp->id)->where('type', 'planning_updated')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('created', $notif->data['action']);
    }

    public function test_removing_user_from_shift_notifies_original_user_with_deleted_action(): void
    {
        $emp  = $this->createEmployee();
        $template = $this->makeTemplate();

        $shift = Shift::withoutEvents(fn () => Shift::create([
            'user_id'           => $emp->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]));

        $shift->update(['user_id' => null]);

        $notif = AppNotification::where('user_id', $emp->id)->where('type', 'planning_updated')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('deleted', $notif->data['action']);
    }

    public function test_reassigning_shift_notifies_both_old_and_new_user(): void
    {
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();
        $template = $this->makeTemplate();

        $shift = Shift::withoutEvents(fn () => Shift::create([
            'user_id'           => $emp1->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]));

        $shift->update(['user_id' => $emp2->id]);

        $deletedNotif = AppNotification::where('user_id', $emp1->id)
            ->where('type', 'planning_updated')
            ->first();
        $this->assertNotNull($deletedNotif);
        $this->assertEquals('deleted', $deletedNotif->data['action']);

        $createdNotif = AppNotification::where('user_id', $emp2->id)
            ->where('type', 'planning_updated')
            ->first();
        $this->assertNotNull($createdNotif);
        $this->assertEquals('created', $createdNotif->data['action']);
    }

    public function test_updating_notes_without_user_change_does_not_trigger_notification(): void
    {
        $emp      = $this->createEmployee();
        $template = $this->makeTemplate();

        $shift = Shift::withoutEvents(fn () => Shift::create([
            'user_id'           => $emp->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]));

        $shift->update(['notes' => 'Bring extra till tape']);

        $this->assertDatabaseMissing('app_notifications', ['type' => 'planning_updated']);
    }

    // -------------------------------------------------------------------------
    // deleted()
    // -------------------------------------------------------------------------

    public function test_deleting_assigned_shift_notifies_assigned_user_with_deleted_action(): void
    {
        $emp = $this->createEmployee();
        $template = $this->makeTemplate();

        $shift = Shift::withoutEvents(fn () => Shift::create([
            'user_id'           => $emp->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]));

        $shift->delete();

        $notif = AppNotification::where('user_id', $emp->id)->where('type', 'planning_updated')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('deleted', $notif->data['action']);
    }

    public function test_deleting_unassigned_shift_does_not_notify_anyone(): void
    {
        $template = $this->makeTemplate();

        $shift = Shift::withoutEvents(fn () => Shift::create([
            'shift_template_id' => $template->id,
            'date'              => '2026-06-01',
        ]));

        $shift->delete();

        $this->assertDatabaseMissing('app_notifications', ['type' => 'planning_updated']);
    }

    // -------------------------------------------------------------------------
    // Approve swap: withoutEvents suppresses shift observer
    // -------------------------------------------------------------------------

    public function test_shift_observer_is_suppressed_during_swap_approval(): void
    {
        $manager = $this->createManager();
        $emp1    = $this->createEmployee();
        $emp2    = $this->createEmployee();
        $s1 = Shift::withoutEvents(fn () => $this->makeShift($emp1, '2026-06-01'));
        $s2 = Shift::withoutEvents(fn () => $this->makeShift($emp2, '2026-06-02'));

        // Simulate what SwapRequestController::decide does when approving
        Shift::withoutEvents(function () use ($s1, $s2, $emp1, $emp2) {
            $s1->update(['user_id' => $emp2->id]);
            $s2->update(['user_id' => $emp1->id]);
        });

        // No planning_updated notifications should have been created
        $this->assertDatabaseMissing('app_notifications', ['type' => 'planning_updated']);
    }
}

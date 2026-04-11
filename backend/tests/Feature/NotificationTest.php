<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    private function createNotification(int $userId, array $overrides = []): AppNotification
    {
        return AppNotification::create(array_merge([
            'user_id' => $userId,
            'type'    => 'planning_updated',
            'data'    => ['action' => 'created', 'date' => '2026-06-01', 'shift_type' => 'morning', 'shift_id' => 1],
            'read_at' => null,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_user_can_fetch_their_notifications(): void
    {
        $user = $this->asEmployee();
        $this->createNotification($user->id);
        $this->createNotification($user->id);

        $response = $this->getJson('/api/notifications')->assertOk();
        $this->assertCount(2, $response->json('notifications'));
    }

    public function test_user_only_sees_their_own_notifications(): void
    {
        $user  = $this->asEmployee();
        $other = $this->createEmployee();

        $this->createNotification($user->id);
        $this->createNotification($other->id);
        $this->createNotification($other->id);

        $response = $this->getJson('/api/notifications')->assertOk();
        $this->assertCount(1, $response->json('notifications'));
    }

    public function test_response_includes_correct_unread_count(): void
    {
        $user = $this->asEmployee();
        $this->createNotification($user->id);                             // unread
        $this->createNotification($user->id, ['read_at' => now()]);       // read

        $response = $this->getJson('/api/notifications')->assertOk();
        $this->assertEquals(1, $response->json('unread_count'));
    }

    public function test_notifications_are_ordered_most_recent_first(): void
    {
        $user = $this->asEmployee();

        $old = $this->createNotification($user->id);
        $old->forceFill(['created_at' => now()->subMinutes(5)])->save();

        $new = $this->createNotification($user->id);

        $response = $this->getJson('/api/notifications')->assertOk();
        $ids      = collect($response->json('notifications'))->pluck('id');
        $this->assertEquals($new->id, $ids->first());
        $this->assertEquals($old->id, $ids->last());
    }

    public function test_at_most_50_notifications_are_returned(): void
    {
        $user = $this->asEmployee();
        for ($i = 0; $i < 55; $i++) {
            $this->createNotification($user->id);
        }

        $response = $this->getJson('/api/notifications')->assertOk();
        $this->assertCount(50, $response->json('notifications'));
    }

    public function test_unauthenticated_user_cannot_fetch_notifications(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Mark read
    // -------------------------------------------------------------------------

    public function test_user_can_mark_their_notification_as_read(): void
    {
        $user  = $this->asEmployee();
        $notif = $this->createNotification($user->id);

        $this->putJson("/api/notifications/{$notif->id}/read")
            ->assertOk()
            ->assertJsonStructure(['read_at']);

        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $this->asEmployee();
        $other = $this->createEmployee();
        $notif = $this->createNotification($other->id);

        $this->putJson("/api/notifications/{$notif->id}/read")->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Mark all read
    // -------------------------------------------------------------------------

    public function test_user_can_mark_all_their_notifications_as_read(): void
    {
        $user = $this->asEmployee();
        $this->createNotification($user->id);
        $this->createNotification($user->id);

        $this->putJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJson(['success' => true]);

        $unread = AppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
        $this->assertEquals(0, $unread);
    }

    public function test_mark_all_read_does_not_affect_other_users(): void
    {
        $user  = $this->asEmployee();
        $other = $this->createEmployee();
        $this->createNotification($other->id);

        $this->putJson('/api/notifications/read-all');

        $otherUnread = AppNotification::where('user_id', $other->id)->whereNull('read_at')->count();
        $this->assertEquals(1, $otherUnread);
    }
}

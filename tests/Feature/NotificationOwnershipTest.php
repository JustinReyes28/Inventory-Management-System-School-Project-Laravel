<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;

class NotificationOwnershipTest extends InventoryTestCase
{
    public function test_notification_index_only_returns_notifications_for_the_signed_in_user(): void
    {
        $owner = $this->createUser('employee', 'notification.owner');
        $other = $this->createUser('employee', 'notification.other');

        $this->createNotification($owner->id, 'Owner notification', 'Only the owner should see this.');
        $this->createNotification($other->id, 'Other notification', 'This belongs to another user.');

        $this->actingAs($owner)
            ->get('/notifications')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Notifications/Index'))
            ->assertSee('Owner notification')
            ->assertDontSee('Other notification');
    }

    public function test_a_user_can_mark_only_their_own_notification_as_read(): void
    {
        $owner = $this->createUser('employee', 'notification.owner.read');
        $other = $this->createUser('employee', 'notification.other.read');
        $ownerNotification = $this->createNotification($owner->id, 'Owner read notification');
        $otherNotification = $this->createNotification($other->id, 'Other read notification');

        $response = $this->actingAs($owner)->patch('/notifications/'.$ownerNotification.'/read');
        $this->assertContains($response->status(), [200, 204, 302]);

        $this->assertDatabaseHas('notifications', [
            'id' => $ownerNotification,
            'user_id' => $owner->id,
            'is_read' => 1,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification,
            'user_id' => $other->id,
            'is_read' => 0,
        ]);

        $forbidden = $this->actingAs($owner)->patch('/notifications/'.$otherNotification.'/read');
        $this->assertContains($forbidden->status(), [403, 404]);

        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification,
            'user_id' => $other->id,
            'is_read' => 0,
        ]);
    }

    public function test_mark_all_read_is_scoped_to_the_authenticated_user(): void
    {
        $owner = $this->createUser('employee', 'notification.owner.all');
        $other = $this->createUser('employee', 'notification.other.all');
        $first = $this->createNotification($owner->id, 'Owner unread one');
        $second = $this->createNotification($owner->id, 'Owner unread two');
        $otherNotification = $this->createNotification($other->id, 'Other unread');

        $response = $this->actingAs($owner)->patch('/notifications/read-all');
        $this->assertContains($response->status(), [200, 204, 302]);

        $this->assertDatabaseHas('notifications', ['id' => $first, 'is_read' => 1]);
        $this->assertDatabaseHas('notifications', ['id' => $second, 'is_read' => 1]);
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification,
            'user_id' => $other->id,
            'is_read' => 0,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_their_own_notifications(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        Notification::factory()->create(['user_id' => $teacher->id, 'title' => 'Mine']);
        Notification::factory()->create(['user_id' => $otherTeacher->id, 'title' => 'NotMine']);

        $response = $this->actingAs($teacher)->get(route('notifications.index'));

        $response->assertSee('Mine');
        $response->assertDontSee('NotMine');
    }

    public function test_unread_count_endpoint_returns_correct_count(): void
    {
        $teacher = User::factory()->teacher()->create();
        Notification::factory()->count(2)->create(['user_id' => $teacher->id]);
        Notification::factory()->create(['user_id' => $teacher->id, 'read_at' => now()]);

        $response = $this->actingAs($teacher)->getJson(route('notifications.unread-count'));

        $response->assertOk()->assertJson(['count' => 2]);
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $teacher = User::factory()->teacher()->create();
        $notification = Notification::factory()->create(['user_id' => $teacher->id]);

        $this->actingAs($teacher)->patch(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $notification = Notification::factory()->create(['user_id' => $otherTeacher->id]);

        $this->actingAs($teacher)->patch(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $teacher = User::factory()->teacher()->create();
        Notification::factory()->count(3)->create(['user_id' => $teacher->id]);

        $this->actingAs($teacher)->patch(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $teacher->notifications()->whereNull('read_at')->count());
    }

    public function test_mark_all_as_read_does_not_affect_other_users_notifications(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        Notification::factory()->create(['user_id' => $teacher->id]);
        $othersNotification = Notification::factory()->create(['user_id' => $otherTeacher->id]);

        $this->actingAs($teacher)->patch(route('notifications.read-all'));

        $this->assertNull($othersNotification->fresh()->read_at);
    }

    public function test_notification_bell_shows_unread_count_in_navigation(): void
    {
        $teacher = User::factory()->teacher()->create();
        Notification::factory()->count(3)->create(['user_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertSee('count: 3', false);
    }
}

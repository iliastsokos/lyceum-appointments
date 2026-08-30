<?php

namespace Tests\Feature\Guardian;

use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_can_no_longer_add_a_child(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->post('/guardian/children', [
            'first_name' => 'Maria', 'last_name' => 'Papadopoulou', 'class' => 'B1',
        ])->assertNotFound();

        $this->assertSame(0, $guardian->children()->count());
    }

    public function test_guardian_can_no_longer_edit_own_child(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $this->actingAs($guardian)->put('/guardian/children/'.$child->id, [
            'first_name' => 'Updated', 'last_name' => $child->last_name, 'class' => $child->class,
        ])->assertNotFound();

        $this->assertNotSame('Updated', $child->fresh()->first_name);
    }

    public function test_guardian_can_no_longer_delete_own_child(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $this->actingAs($guardian)->delete('/guardian/children/'.$child->id)->assertNotFound();

        $this->assertDatabaseHas('children', ['id' => $child->id]);
    }

    public function test_guardian_dashboard_only_shows_own_children(): void
    {
        $guardianA = User::factory()->guardian()->create();
        $guardianB = User::factory()->guardian()->create();
        Child::factory()->for($guardianA, 'guardian')->create(['first_name' => 'MineChild']);
        Child::factory()->for($guardianB, 'guardian')->create(['first_name' => 'OtherChild']);

        $response = $this->actingAs($guardianA)->get(route('guardian.dashboard'));

        $response->assertSee('MineChild');
        $response->assertDontSee('OtherChild');
    }

    public function test_guardian_dashboard_has_no_edit_or_delete_links_for_children(): void
    {
        $guardian = User::factory()->guardian()->create();
        Child::factory()->for($guardian, 'guardian')->create();

        $response = $this->actingAs($guardian)->get(route('guardian.dashboard'));

        $response->assertDontSee('Επεξεργασία');
        $response->assertDontSee('Διαγραφή');
    }
}

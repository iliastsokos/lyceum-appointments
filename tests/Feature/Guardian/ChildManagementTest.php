<?php

namespace Tests\Feature\Guardian;

use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_can_add_a_child(): void
    {
        $guardian = User::factory()->guardian()->create();

        $response = $this->actingAs($guardian)->post(route('guardian.children.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Papadopoulou',
            'class' => 'B1',
        ]);

        $response->assertRedirect(route('guardian.dashboard'));
        $this->assertDatabaseHas('children', [
            'guardian_id' => $guardian->id,
            'first_name' => 'Maria',
            'last_name' => 'Papadopoulou',
            'class' => 'B1',
        ]);
    }

    public function test_guardian_can_add_multiple_children(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->post(route('guardian.children.store'), [
            'first_name' => 'Maria', 'last_name' => 'Papadopoulou', 'class' => 'B1',
        ]);
        $this->actingAs($guardian)->post(route('guardian.children.store'), [
            'first_name' => 'Nikos', 'last_name' => 'Papadopoulos', 'class' => 'G2',
        ]);

        $this->assertSame(2, $guardian->children()->count());
    }

    public function test_guardian_can_edit_own_child(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $response = $this->actingAs($guardian)->put(route('guardian.children.update', $child), [
            'first_name' => 'Updated',
            'last_name' => $child->last_name,
            'class' => $child->class,
        ]);

        $response->assertRedirect(route('guardian.dashboard'));
        $this->assertSame('Updated', $child->fresh()->first_name);
    }

    public function test_guardian_cannot_edit_another_guardians_child(): void
    {
        $guardianA = User::factory()->guardian()->create();
        $guardianB = User::factory()->guardian()->create();
        $childOfB = Child::factory()->for($guardianB, 'guardian')->create();

        $this->actingAs($guardianA)
            ->put(route('guardian.children.update', $childOfB), ['first_name' => 'Hacked', 'last_name' => 'Name', 'class' => 'A1'])
            ->assertForbidden();

        $this->assertNotSame('Hacked', $childOfB->fresh()->first_name);
    }

    public function test_guardian_cannot_view_another_guardians_child_edit_form(): void
    {
        $guardianA = User::factory()->guardian()->create();
        $guardianB = User::factory()->guardian()->create();
        $childOfB = Child::factory()->for($guardianB, 'guardian')->create();

        $this->actingAs($guardianA)->get(route('guardian.children.edit', $childOfB))->assertForbidden();
    }

    public function test_guardian_can_delete_own_child(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $this->actingAs($guardian)->delete(route('guardian.children.destroy', $child))
            ->assertRedirect(route('guardian.dashboard'));

        $this->assertDatabaseMissing('children', ['id' => $child->id]);
    }

    public function test_guardian_cannot_delete_another_guardians_child(): void
    {
        $guardianA = User::factory()->guardian()->create();
        $guardianB = User::factory()->guardian()->create();
        $childOfB = Child::factory()->for($guardianB, 'guardian')->create();

        $this->actingAs($guardianA)->delete(route('guardian.children.destroy', $childOfB))->assertForbidden();

        $this->assertDatabaseHas('children', ['id' => $childOfB->id]);
    }

    public function test_guardian_can_view_create_and_edit_forms(): void
    {
        $guardian = User::factory()->guardian()->create();
        $child = Child::factory()->for($guardian, 'guardian')->create();

        $this->actingAs($guardian)->get(route('guardian.children.create'))->assertOk();
        $this->actingAs($guardian)->get(route('guardian.children.edit', $child))->assertOk();
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
}

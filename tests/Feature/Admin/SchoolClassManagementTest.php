<?php

namespace Tests\Feature\Admin;

use App\Models\Child;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_school_classes_index(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.school-classes.index'));

        $response->assertOk();
        $response->assertSee('A1');
    }

    public function test_admin_can_create_a_school_class(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.school-classes.store'), [
            'name' => 'D1',
        ]);

        $response->assertRedirect(route('admin.school-classes.index'));
        $this->assertDatabaseHas('school_classes', ['name' => 'D1']);
    }

    public function test_creating_a_school_class_rejects_greek_characters(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.school-classes.store'), [
            'name' => 'Α1',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('school_classes', ['name' => 'Α1']);
    }

    public function test_creating_a_duplicate_school_class_name_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        SchoolClass::factory()->create(['name' => 'D1']);

        $response = $this->actingAs($admin)->post(route('admin.school-classes.store'), [
            'name' => 'D1',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, SchoolClass::where('name', 'D1')->count());
    }

    public function test_admin_can_rename_a_school_class_and_existing_children_follow(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolClass = SchoolClass::factory()->create(['name' => 'D1']);
        $child = Child::factory()->create(['class' => 'D1']);

        $response = $this->actingAs($admin)->put(route('admin.school-classes.update', $schoolClass), [
            'name' => 'D2',
        ]);

        $response->assertRedirect(route('admin.school-classes.index'));
        $this->assertSame('D2', $schoolClass->fresh()->name);
        $this->assertSame('D2', $child->fresh()->class);
    }

    public function test_admin_can_delete_a_school_class_with_no_children(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolClass = SchoolClass::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.school-classes.destroy', $schoolClass));

        $response->assertRedirect(route('admin.school-classes.index'));
        $this->assertDatabaseMissing('school_classes', ['id' => $schoolClass->id]);
    }

    public function test_admin_cannot_delete_a_school_class_with_children(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolClass = SchoolClass::factory()->create(['name' => 'D1']);
        Child::factory()->create(['class' => 'D1']);

        $response = $this->actingAs($admin)->delete(route('admin.school-classes.destroy', $schoolClass));

        $response->assertSessionHasErrors('schoolClass');
        $this->assertDatabaseHas('school_classes', ['id' => $schoolClass->id]);
    }

    public function test_non_admin_cannot_manage_school_classes(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian)->get(route('admin.school-classes.index'))->assertForbidden();
        $this->actingAs($guardian)->post(route('admin.school-classes.store'), ['name' => 'D1'])->assertForbidden();
    }

    public function test_guardian_sees_admin_managed_classes_when_adding_a_child(): void
    {
        $guardian = User::factory()->guardian()->create();
        SchoolClass::factory()->create(['name' => 'D1']);

        $response = $this->actingAs($guardian)->post(route('guardian.children.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Papadopoulou',
            'class' => 'D1',
        ]);

        $response->assertRedirect(route('guardian.dashboard'));
        $this->assertDatabaseHas('children', ['first_name' => 'Maria', 'class' => 'D1']);
    }
}

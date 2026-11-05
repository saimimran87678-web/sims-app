<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ClassManagerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $session1;
    protected $session2;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed license status
        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session1 = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        $this->session2 = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => false,
        ]);
    }

    public function test_can_view_institutional_classrooms()
    {
        $this->actingAs($this->admin);

        $class1 = Classes::create([
            'name' => 'Class 8A',
            'numeric_value' => 8,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $class2 = Classes::create([
            'name' => 'Class 9B',
            'numeric_value' => 9,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->assertViewHas('classes', function ($classes) {
                return $classes->pluck('name')->contains('Class 8A') &&
                       $classes->pluck('name')->contains('Class 9B');
            });
    }

    public function test_can_create_new_classroom()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('name', '10')
            ->call('save')
            ->assertHasNoErrors();

        // Verify permanent classroom exists
        $this->assertTrue(
            Classes::where('name', 'Class 10')
                ->where('numeric_value', 10)
                ->where('is_active', true)
                ->exists()
        );
    }

    public function test_classroom_can_be_archived_and_reactivated()
    {
        $this->actingAs($this->admin);

        $class = Classes::create([
            'name' => 'Class 12C',
            'numeric_value' => 12,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        // Archive the classroom
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('toggleActiveClass', $class->id)
            ->assertHasNoErrors();

        $this->assertFalse($class->fresh()->is_active);

        // Reactivate the classroom
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('toggleActiveClass', $class->id)
            ->assertHasNoErrors();

        $this->assertTrue($class->fresh()->is_active);
    }

    public function test_can_configure_permanent_promotes_to_progression()
    {
        $this->actingAs($this->admin);

        $class6 = Classes::create([
            'name' => 'Class 6A',
            'numeric_value' => 6,
            'shift_type' => 'regular',
        ]);

        $class7 = Classes::create([
            'name' => 'Class 7A',
            'numeric_value' => 7,
            'shift_type' => 'regular',
        ]);

        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('updateNextClass', $class6->id, $class7->id)
            ->assertHasNoErrors();

        $this->assertEquals($class7->id, $class6->fresh()->next_class_id);
    }

    public function test_class_with_enrollment_cannot_be_deleted()
    {
        $this->actingAs($this->admin);

        $class = Classes::create([
            'name' => 'Class 8A',
            'numeric_value' => 8,
            'shift_type' => 'regular',
        ]);

        $student = \App\Models\Student::create([
            'name' => 'John Doe',
            'admission_no' => 'ADM-123',
        ]);

        \App\Models\Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'regular',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        // Attempt to delete via ClassManager
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('confirmDelete', $class->id);

        // Verify class was not deleted
        $this->assertTrue(Classes::where('id', $class->id)->exists());

        // Attempting force delete directly on the model throws exception
        $this->expectException(\DomainException::class);
        $class->forceDelete();
    }

    public function test_renaming_classroom_protects_grade_level_when_enrollments_exist()
    {
        $this->actingAs($this->admin);

        $class = Classes::create([
            'name' => 'Class 8',
            'numeric_value' => 8,
            'shift_type' => 'regular',
        ]);

        $student = \App\Models\Student::create([
            'name' => 'Jane Doe',
            'admission_no' => 'ADM-124',
        ]);

        \App\Models\Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'regular',
            'roll_number' => '2',
            'status' => 'active',
        ]);

        // Rename class cosmetic name to "Class 8-Rose"
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('renamingClassId', $class->id)
            ->set('renamingClassName', 'Class 8-Rose')
            ->call('saveClassName')
            ->assertHasNoErrors();

        $fresh = $class->fresh();
        $this->assertEquals('Class 8-Rose', $fresh->name);
        $this->assertEquals(8, $fresh->numeric_value); // Grade level preserved
    }
}

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

    public function test_early_childhood_education_classes_and_hyphenated_sections_support()
    {
        $this->actingAs($this->admin);

        // 1. Create Early Education classes without forced "Class" prefix
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('name', 'Nursery - Rose')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('name', 'Playgroup - Blue')
            ->call('save')
            ->assertHasNoErrors();

        // 2. Create custom named section class
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('name', 'Class 7 - Iqbal')
            ->call('save')
            ->assertHasNoErrors();

        $nursery = Classes::where('name', 'Nursery - Rose')->first();
        $playgroup = Classes::where('name', 'Playgroup - Blue')->first();
        $class7 = Classes::where('name', 'Class 7 - Iqbal')->first();

        $this->assertNotNull($nursery);
        $this->assertNotNull($playgroup);
        $this->assertNotNull($class7);

        // Numeric value detection
        $this->assertEquals(-2, $nursery->numeric_value);
        $this->assertEquals(-3, $playgroup->numeric_value);
        $this->assertEquals(7, $class7->numeric_value);

        // Model accessors
        $this->assertEquals('Nursery', $nursery->grade_name);
        $this->assertEquals('Rose', $nursery->section_name);
        $this->assertEquals('Nursery — Rose', $nursery->display_name);
        $this->assertTrue($nursery->is_early_education);

        $this->assertEquals('Playgroup', $playgroup->grade_name);
        $this->assertEquals('Blue', $playgroup->section_name);
        $this->assertTrue($playgroup->is_early_education);

        $this->assertEquals('Class 7', $class7->grade_name);
        $this->assertEquals('Iqbal', $class7->section_name);
        $this->assertEquals('Class 7 — Iqbal', $class7->display_name);
        $this->assertFalse($class7->is_early_education);
    }

    public function test_archived_classes_are_separated_from_active_classes()
    {
        $this->actingAs($this->admin);

        $activeRoom = Classes::create([
            'name' => 'Class 6 - Active',
            'numeric_value' => 6,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $archivedRoom = Classes::create([
            'name' => 'Class 6 - Inactive',
            'numeric_value' => 6,
            'shift_type' => 'regular',
            'is_active' => false,
        ]);

        // Default: Active view only shows active room
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->assertSet('showArchived', false)
            ->assertViewHas('classes', function ($classes) use ($activeRoom, $archivedRoom) {
                return $classes->contains('id', $activeRoom->id) &&
                      !$classes->contains('id', $archivedRoom->id);
            });

        // Toggle to Archived view: only shows archived room
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('toggleArchived')
            ->assertSet('showArchived', true)
            ->assertViewHas('classes', function ($classes) use ($activeRoom, $archivedRoom) {
                return !$classes->contains('id', $activeRoom->id) &&
                        $classes->contains('id', $archivedRoom->id);
            });
    }

    public function test_mistaken_classroom_with_zero_records_can_be_directly_deleted_permanently()
    {
        $this->actingAs($this->admin);

        $mistaken = Classes::create([
            'name' => 'Class 99 - Mistake',
            'numeric_value' => 99,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('deleteClassDirectly', $mistaken->id)
            ->assertHasNoErrors();

        // Assert record is permanently purged
        $this->assertNull(Classes::withTrashed()->find($mistaken->id));
    }

    public function test_class_created_in_new_session_is_hidden_in_old_completed_session()
    {
        $this->actingAs($this->admin);

        // Session 1: completed / archived
        $this->session1->update(['is_active' => false, 'is_archived' => true]);
        // Session 2: active current session
        $this->session2->update(['is_active' => true, 'is_archived' => false]);

        // Create class in new active Session 2
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('name', 'Class 10 - Future')
            ->call('save')
            ->assertHasNoErrors();

        $newClass = Classes::where('name', 'Class 10 - Future')->first();
        $this->assertNotNull($newClass);
        $this->assertEquals($this->session2->id, $newClass->academic_session_id);

        // When viewing Session 2 (active), class is visible
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->assertViewHas('classes', function ($classes) use ($newClass) {
                return $classes->contains('id', $newClass->id);
            });

        // When viewing Session 1 (old completed session), new class MUST NOT appear
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session1->id)
            ->assertViewHas('classes', function ($classes) use ($newClass) {
                return !$classes->contains('id', $newClass->id);
            });
    }

    public function test_class_archived_in_new_session_remains_active_in_old_completed_session()
    {
        $this->actingAs($this->admin);

        // Create a classroom that was active in Session 1
        $class = Classes::create([
            'name' => 'Class 7 - Heritage',
            'numeric_value' => 7,
            'shift_type' => 'regular',
            'academic_session_id' => $this->session1->id,
            'is_active' => true,
        ]);

        // Session 1 is completed/archived, Session 2 is current
        $this->session1->update(['is_active' => false, 'is_archived' => true]);
        $this->session2->update(['is_active' => true, 'is_archived' => false]);

        // In Session 2 (active), archive the classroom
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->call('archiveClass', $class->id)
            ->assertHasNoErrors();

        $fresh = $class->fresh();
        $this->assertFalse($fresh->is_active);
        $this->assertEquals($this->session2->id, $fresh->archived_academic_session_id);

        // In Session 2: class is NOT in active list
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('showArchived', false)
            ->assertViewHas('classes', function ($classes) use ($class) {
                return !$classes->contains('id', $class->id);
            });

        // In Session 2: class IS in archived list
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('showArchived', true)
            ->assertViewHas('classes', function ($classes) use ($class) {
                return $classes->contains('id', $class->id);
            });

        // In Session 1 (historical completed session):
        // The classroom was historically active throughout Session 1!
        // Active list MUST contain the class
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session1->id)
            ->set('showArchived', false)
            ->assertViewHas('classes', function ($classes) use ($class) {
                return $classes->contains('id', $class->id);
            });

        // Archived list in Session 1 MUST NOT contain the class
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session1->id)
            ->set('showArchived', true)
            ->assertViewHas('classes', function ($classes) use ($class) {
                return !$classes->contains('id', $class->id);
            });
    }

    public function test_historical_session_blocks_mutative_operations()
    {
        $this->actingAs($this->admin);

        // Lock Session 1
        $this->session1->update(['is_active' => false, 'is_archived' => true]);
        $this->session2->update(['is_active' => true, 'is_archived' => false]);
        AcademicSession::flushActiveSessionMemo();

        $class = Classes::create([
            'name' => 'Class 8 - Sealed',
            'numeric_value' => 8,
            'shift_type' => 'regular',
            'academic_session_id' => $this->session1->id,
            'is_active' => true,
        ]);

        // Attempting to add a class in locked session
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session1->id)
            ->set('name', 'Illegal New Class')
            ->call('save')
            ->assertSee('New classrooms cannot be created in a concluded academic session');

        $this->assertFalse(Classes::where('name', 'Illegal New Class')->exists());

        // Attempting to archive in locked session
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session1->id)
            ->call('archiveClass', $class->id)
            ->assertSee('Historical academic sessions are sealed. Classrooms cannot be archived in a concluded session.');

        $this->assertTrue($class->fresh()->is_active);

        // Attempting to delete in locked session
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session1->id)
            ->call('deleteClassDirectly', $class->id)
            ->assertSee('Historical academic sessions are sealed. Classrooms cannot be deleted in a concluded session.');

        $this->assertTrue(Classes::where('id', $class->id)->exists());
    }
}

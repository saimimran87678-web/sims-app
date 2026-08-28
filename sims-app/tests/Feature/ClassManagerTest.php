<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

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

    public function test_can_view_classes_in_session()
    {
        $this->actingAs($this->admin);

        // Create a class link it to session1
        $class1 = Classes::create([
            'name' => 'Class 8A',
            'numeric_value' => 8,
            'academic_session_id' => $this->session1->id,
        ]);

        // Create another class link it to session2
        $class2 = Classes::create([
            'name' => 'Class 9B',
            'numeric_value' => 9,
            'academic_session_id' => $this->session2->id,
        ]);

        // Test with session1
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session1->id)
            ->assertViewHas('classes', function ($classes) {
                return $classes->pluck('name')->contains('Class 8A') &&
                       !$classes->pluck('name')->contains('Class 9B');
            });

        // Test with session2
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->assertViewHas('classes', function ($classes) {
                return $classes->pluck('name')->contains('Class 9B') &&
                       !$classes->pluck('name')->contains('Class 8A');
            });
    }

    public function test_can_create_new_class_in_session()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session1->id)
            ->set('name', '10')
            ->call('save')
            ->assertHasNoErrors();

        // Verify it was created for session1
        $this->assertTrue(
            Classes::withoutGlobalScope('active_session')
                ->where('name', 'Class 10')
                ->where('academic_session_id', $this->session1->id)
                ->exists()
        );
    }

    public function test_same_class_name_can_exist_in_different_sessions_independently()
    {
        $this->actingAs($this->admin);

        // Create class in session 1
        $class = Classes::create([
            'name' => 'Class 11',
            'numeric_value' => 11,
            'academic_session_id' => $this->session1->id,
        ]);

        // Try to create class with same name in session 2
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('name', '11')
            ->call('save')
            ->assertHasNoErrors();

        // Verify Class 11 exists in both sessions independently
        $this->assertEquals(
            2,
            Classes::withoutGlobalScope('active_session')
                ->where('name', 'Class 11')
                ->count()
        );
    }
    public function test_can_import_classes_and_subjects_from_another_session()
    {
        $this->actingAs($this->admin);

        // 1. Create a class and subject in session 1
        $class1 = Classes::create([
            'name' => 'Class 8A',
            'numeric_value' => 8,
            'academic_session_id' => $this->session1->id,
        ]);
        
        $subject = \App\Models\Subject::create([
            'class_id' => $class1->id,
            'name' => 'Math',
            'code' => 'MTH',
        ]);

        // 2. Import into session 2
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('importSourceSessionId', $this->session1->id)
            ->set('selectedSourceClassIds', [(string)$class1->id])
            ->set('importSubjects', true)
            ->call('importClasses')
            ->assertHasNoErrors();

        // 3. Verify class and subject exists in session 2
        $importedClass = Classes::withoutGlobalScope('active_session')
            ->where('name', 'Class 8A')
            ->where('academic_session_id', $this->session2->id)
            ->first();

        $this->assertNotNull($importedClass);
        $this->assertEquals(8, $importedClass->numeric_value);

        $importedSubject = \App\Models\Subject::where('class_id', $importedClass->id)
            ->where('name', 'Math')
            ->first();
            
        $this->assertNotNull($importedSubject);
        $this->assertEquals('MTH', $importedSubject->code);
    }

    public function test_can_selectively_import_classes_from_another_session()
    {
        $this->actingAs($this->admin);

        // 1. Create two classes in session 1
        $classA = Classes::create([
            'name' => 'Class 8A',
            'numeric_value' => 8,
            'academic_session_id' => $this->session1->id,
        ]);
        $classB = Classes::create([
            'name' => 'Class 8B',
            'numeric_value' => 8,
            'academic_session_id' => $this->session1->id,
        ]);

        // 2. Import ONLY Class 8A into session 2
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('importSourceSessionId', $this->session1->id)
            ->set('selectedSourceClassIds', [(string)$classA->id])
            ->set('importSubjects', false)
            ->call('importClasses')
            ->assertHasNoErrors();

        // 3. Verify Class 8A was imported but Class 8B was NOT
        $importedClassA = Classes::withoutGlobalScope('active_session')
            ->where('name', 'Class 8A')
            ->where('academic_session_id', $this->session2->id)
            ->first();
        $importedClassB = Classes::withoutGlobalScope('active_session')
            ->where('name', 'Class 8B')
            ->where('academic_session_id', $this->session2->id)
            ->first();

        $this->assertNotNull($importedClassA);
        $this->assertNull($importedClassB);
     }

     public function test_promotes_to_selector_requires_final_term_exam()
     {
         $this->actingAs($this->admin);

         // 1. Without any final exam in session1, hasFinalExam should be false
         Livewire::test(\App\Livewire\Admin\ClassManager::class)
             ->set('selectedSessionId', $this->session1->id)
             ->assertViewHas('hasFinalExam', false);

         // 2. Create a Mid-Term exam: should still be false
         \App\Models\Exam::create([
             'name' => 'Mid Term 2025',
             'type' => 'Mid-Term',
             'academic_session_id' => $this->session1->id,
             'start_date' => '2025-09-01',
             'end_date' => '2025-09-15',
         ]);

         Livewire::test(\App\Livewire\Admin\ClassManager::class)
             ->set('selectedSessionId', $this->session1->id)
             ->assertViewHas('hasFinalExam', false);

         // 3. Create a Final-Term exam: hasFinalExam should be true
         \App\Models\Exam::create([
             'name' => 'Final Term 2025',
             'type' => 'Final-Term',
             'academic_session_id' => $this->session1->id,
             'start_date' => '2026-03-01',
             'end_date' => '2026-03-15',
         ]);

         Livewire::test(\App\Livewire\Admin\ClassManager::class)
             ->set('selectedSessionId', $this->session1->id)
             ->assertViewHas('hasFinalExam', true);
     }

     public function test_promotes_to_selector_requires_other_academic_session()
     {
         $this->actingAs($this->admin);

         // 1. If only session1 exists (delete target session), hasNextSession should be false
         $this->session2->delete();

         Livewire::test(\App\Livewire\Admin\ClassManager::class)
             ->set('selectedSessionId', $this->session1->id)
             ->assertViewHas('hasNextSession', false)
             ->assertViewHas('allClasses', function ($classes) {
                 return $classes->isEmpty();
             });

         // 2. Re-create a session: hasNextSession should be true
         $sessionTarget = \App\Models\AcademicSession::create([
             'name' => '2026-2027',
             'start_date' => '2026-04-01',
             'end_date' => '2027-03-31',
             'is_active' => false,
         ]);

                   // Create a class in the next session to verify it is loaded
          $classInTarget = Classes::create([
              "name" => "Target Class 10",
              "numeric_value" => 10,
              "academic_session_id" => $sessionTarget->id,
          ]);

          Livewire::test(\App\Livewire\Admin\ClassManager::class)
              ->set("selectedSessionId", $this->session1->id)
              ->assertViewHas("hasNextSession", true)
              ->assertViewHas("allClasses", function ($classes) use ($classInTarget) {
                  return $classes->count() === 1 && $classes->first()->id === $classInTarget->id;
              });
     }

     public function test_class_with_enrollment_cannot_be_permanently_deleted()
     {
         $this->actingAs($this->admin);

         // 1. Create a class and soft delete it
         $class = Classes::create([
             'name' => 'Class 8A',
             'numeric_value' => 8,
             'academic_session_id' => $this->session1->id,
         ]);
         $class->delete(); // Soft delete

         // 2. Create student and enrollment referencing this class
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
         ]);

         // 3. Try to permanently delete
         Livewire::test(\App\Livewire\Admin\ClassManager::class)
             ->set('selectedSessionId', $this->session1->id)
             ->call('permanentDelete', $class->id);

         // 4. Verify class still exists in trashed list (not hard deleted)
         $this->assertTrue(
             Classes::withoutGlobalScope('active_session')
                 ->onlyTrashed()
                 ->where('id', $class->id)
                 ->exists()
         );
     }
}
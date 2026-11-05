<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\AcademicSession;
use App\Models\Setting;
use App\Models\FeeRecord;
use App\Models\Timetable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class PermanentClassroomArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $session2025;
    protected $session2026;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session2025 = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        $this->session2026 = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => false,
        ]);
    }

    public function test_classroom_persists_across_sessions_like_school_building()
    {
        $this->actingAs($this->admin);

        // Room is constructed
        $room = Classes::create([
            'name' => 'Class 6A',
            'numeric_value' => 6,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        // Student 1 enrolled in 2025-2026 in Room 6A
        $student1 = Student::create(['name' => 'Ali Khan', 'admission_no' => 'ADM-101']);
        Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2025->id,
            'shift_type' => 'regular',
            'roll_number' => '1',
            'status' => 'completed',
        ]);

        // Next year: Session 2026-2027 becomes active
        $this->session2025->update(['is_active' => false]);
        $this->session2026->update(['is_active' => true]);

        // The EXACT same room 6A is still there
        $activeRoom = Classes::find($room->id);
        $this->assertNotNull($activeRoom);
        $this->assertEquals('Class 6A', $activeRoom->name);

        // New student enrolled in 2026-2027 in the SAME room 6A
        $student2 = Student::create(['name' => 'Bilal Ahmed', 'admission_no' => 'ADM-102']);
        Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2026->id,
            'shift_type' => 'regular',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        // Room has both students historically
        $this->assertEquals(2, $room->students()->count());
    }

    public function test_cannot_delete_classroom_with_historical_enrollments()
    {
        $this->actingAs($this->admin);

        $room = Classes::create([
            'name' => 'Class 7A',
            'numeric_value' => 7,
            'shift_type' => 'regular',
        ]);

        $student = Student::create(['name' => 'Tariq', 'admission_no' => 'ADM-103']);
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2025->id,
            'shift_type' => 'regular',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        // Try UI delete
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('confirmDelete', $room->id)
            ->assertSee('cannot be deleted because historical student, exam, or fee records exist');

        $this->assertDatabaseHas('classes', ['id' => $room->id, 'deleted_at' => null]);

        // Force delete via model is blocked by domain guard
        $this->expectException(\DomainException::class);
        $room->forceDelete();
    }

    public function test_database_level_restrict_prevents_deleting_class_with_fee_records()
    {
        $this->actingAs($this->admin);

        $room = Classes::create([
            'name' => 'Class 8A',
            'numeric_value' => 8,
            'shift_type' => 'regular',
        ]);

        $student = Student::create(['name' => 'Hamza', 'admission_no' => 'ADM-104']);

        DB::table('fee_records')->insert([
            'student_id' => $student->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2025->id,
            'period' => '2025-04',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 5000,
            'balance' => 0,
            'status' => 'paid',
            'due_date' => '2025-04-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // UI deletion blocked
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('confirmDelete', $room->id);

        $this->assertDatabaseHas('classes', ['id' => $room->id]);

        // Raw SQL delete is blocked by SQLite engine ON DELETE RESTRICT
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::statement("DELETE FROM classes WHERE id = {$room->id}");
    }

    public function test_empty_unused_classroom_can_be_safely_deleted()
    {
        $this->actingAs($this->admin);

        // Created by mistake with zero linked data
        $room = Classes::create([
            'name' => 'Class Typo99',
            'numeric_value' => 99,
            'shift_type' => 'regular',
        ]);

        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('confirmDelete', $room->id);

        // Soft deleted
        $this->assertSoftDeleted('classes', ['id' => $room->id]);

        // Permanently deleted
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('permanentDelete', $room->id);

        $this->assertDatabaseMissing('classes', ['id' => $room->id]);
    }

    public function test_deleting_academic_session_never_deletes_permanent_classrooms()
    {
        $this->actingAs($this->admin);

        $room = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'shift_type' => 'regular',
            'academic_session_id' => $this->session2026->id,
        ]);

        // Delete empty session2026
        Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->call('delete', $this->session2026->id)
            ->assertHasNoErrors();

        // Session was deleted
        $this->assertDatabaseMissing('academic_sessions', ['id' => $this->session2026->id]);

        // BUT Room 10A STILL STANDS!
        $this->assertDatabaseHas('classes', ['id' => $room->id, 'name' => 'Class 10A']);
    }

    public function test_archive_deactivates_classroom_without_corrupting_history()
    {
        $this->actingAs($this->admin);

        $room = Classes::create([
            'name' => 'Class 12Old',
            'numeric_value' => 12,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $student = Student::create(['name' => 'Graduate Student', 'admission_no' => 'ADM-105']);
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2025->id,
            'shift_type' => 'regular',
            'roll_number' => '5',
            'status' => 'completed',
        ]);

        // Archive the room
        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->call('toggleActiveClass', $room->id);

        $this->assertFalse($room->fresh()->is_active);

        // Historical enrollment still links cleanly to the room
        $enrollment = Enrollment::where('student_id', $student->id)->first();
        $this->assertEquals($room->id, $enrollment->class_id);
        $this->assertEquals('Class 12Old', $enrollment->class->name);
    }
}

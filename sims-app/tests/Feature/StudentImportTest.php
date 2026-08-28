<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AcademicSession;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Livewire\Admin\StudentImportManager;
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $class10A;
    private $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        $this->class10A = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $this->session->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');
        $this->actingAs($this->admin);

        Storage::fake('local');
    }

    public function test_can_upload_and_preview_valid_csv(): void
    {
        $csvContent = "Name,Father Name,Class Name,Admission No,Roll No,Phone,Gender,Shift\n" .
            "John Doe,Richard Doe,Class 10A,ADM-111,10,03001234567,Male,morning\n" .
            "Jane Smith,Robert Smith,Class 10A,ADM-222,11,03007654321,Female,morning\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        Livewire::test(StudentImportManager::class)
            ->set('file', $file)
            ->assertSet('showPreview', true)
            ->assertViewHas('sessions')
            ->assertCount('previewRows', 2)
            ->assertSet('previewRows.0.name', 'John Doe')
            ->assertSet('previewRows.0.is_valid', true)
            ->assertSet('previewRows.1.name', 'Jane Smith')
            ->assertSet('previewRows.1.is_valid', true);
    }

    public function test_flags_errors_on_invalid_csv_rows(): void
    {
        // One missing class, one duplicate admission no, one missing name
        Student::create([
            'name' => 'Existing Boy',
            'admission_no' => 'ADM-DUP',
            'class_id' => $this->class10A->id,
        ]);

        $csvContent = "Name,Father Name,Class Name,Admission No,Roll No,Phone,Gender,Shift\n" .
            ",Richard Doe,Class 10A,ADM-111,10,03001234567,Male,morning\n" . // Missing name
            "Jane Smith,Robert Smith,Class 999,ADM-222,11,03007654321,Female,morning\n" . // Non-existent class
            "John Junior,John Senior,Class 10A,ADM-DUP,12,03002223333,Male,morning\n"; // Duplicate Admission No

        $file = UploadedFile::fake()->createWithContent('students_invalid.csv', $csvContent);

        Livewire::test(StudentImportManager::class)
            ->set('file', $file)
            ->assertSet('showPreview', true)
            ->assertSet('previewRows.0.is_valid', false)
            ->assertSet('previewRows.1.is_valid', false)
            ->assertSet('previewRows.2.is_valid', false);
    }

    public function test_can_process_import_for_valid_rows(): void
    {
        $csvContent = "Name,Father Name,Class Name,Admission No,Roll No,Phone,Gender,Shift\n" .
            "John Doe,Richard Doe,Class 10A,ADM-111,10,03001234567,Male,morning\n" .
            "Jane Smith,Robert Smith,Class 10A,ADM-222,11,03007654321,Female,morning\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        Livewire::test(StudentImportManager::class)
            ->set('file', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('showPreview', false);

        // Verify students and enrollments in database
        $this->assertDatabaseHas('students', [
            'name' => 'John Doe',
            'admission_no' => 'ADM-111',
            'father_name' => 'Richard Doe',
        ]);

        $this->assertDatabaseHas('students', [
            'name' => 'Jane Smith',
            'admission_no' => 'ADM-222',
            'father_name' => 'Robert Smith',
        ]);

        $john = Student::where('admission_no', 'ADM-111')->first();
        $this->assertNotNull($john);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $john->id,
            'class_id' => $this->class10A->id,
            'academic_session_id' => $this->session->id,
            'roll_number' => '10',
        ]);
    }
}

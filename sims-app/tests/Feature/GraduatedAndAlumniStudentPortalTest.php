<?php

namespace Tests\Feature;

use App\Livewire\Admin\AcademicSessionManager;
use App\Livewire\Admin\StudentManager;
use App\Livewire\Student\PortalDashboard;
use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GraduatedAndAlumniStudentPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicSession $session1;
    private AcademicSession $session2;
    private Classes $class10;
    private Classes $class7;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session1 = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'shift_type' => 'Regular',
            'is_active' => false,
            'is_archived' => true,
        ]);

        $this->session2 = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
            'is_archived' => false,
        ]);

        $this->class10 = Classes::create([
            'name' => 'Class 10',
            'numeric_value' => 10,
            'shift_type' => 'regular',
            'academic_session_id' => $this->session1->id,
            'next_class_id' => null, // Terminal class
        ]);

        $this->class7 = Classes::create([
            'name' => 'Class 7',
            'numeric_value' => 7,
            'shift_type' => 'regular',
            'academic_session_id' => $this->session1->id,
        ]);
    }

    public function test_promotion_sets_graduated_status_on_student_and_enrollment(): void
    {
        $student = Student::create([
            'name' => 'Ali Raza',
            'admission_no' => 'ADM-1001',
            'father_cnic' => '37405-1111111-1',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class10->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'regular',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);

        // Run promotion from session 1 to session 2
        $component = Livewire::test(AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion');

        // Verify the terminal student defaulted to graduated
        $preview = $component->get('promotionPreview');
        $this->assertNotEmpty($preview);
        $this->assertEquals('graduated', $preview[0]['status']);

        // Save promotion
        $component->call('savePromotion');

        // Assert enrollment and student status are standardized to graduated
        $this->assertEquals('graduated', $enrollment->fresh()->status);
        $this->assertEquals('graduated', $student->fresh()->status);

        // Assert no enrollment in session 2 was created for the graduated student
        $this->assertFalse(
            Enrollment::where('student_id', $student->id)
                ->where('academic_session_id', $this->session2->id)
                ->exists()
        );
    }

    public function test_graduated_student_visible_in_student_manager_across_sessions(): void
    {
        $student = Student::create([
            'name' => 'Muhammad Saim',
            'admission_no' => '5849',
            'father_cnic' => '37405-1234567-2',
            'status' => 'graduated',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class10->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'regular',
            'roll_number' => '1',
            'status' => 'graduated',
        ]);

        $this->actingAs($this->admin);

        // Admin views new active session (session 2)
        // 1. Filter: graduated -> Saim MUST be present
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('filterStatus', 'graduated')
            ->assertViewHas('students', function ($students) use ($student) {
                return $students->contains('id', $student->id);
            });

        // 2. Filter: alumni -> Saim MUST be present
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('filterStatus', 'alumni')
            ->assertViewHas('students', function ($students) use ($student) {
                return $students->contains('id', $student->id);
            });

        // 3. Filter: active -> Saim MUST NOT be present in active roster
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->set('filterStatus', 'active')
            ->assertViewHas('students', function ($students) use ($student) {
                return !$students->contains('id', $student->id);
            });
    }

    public function test_graduated_student_portal_shows_notice_and_withholds_active_data(): void
    {
        $student = Student::create([
            'name' => 'Tariq Mehmood',
            'admission_no' => 'ADM-9999',
            'father_cnic' => '37405-9999999-1',
            'status' => 'graduated',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class10->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'regular',
            'roll_number' => '99',
            'status' => 'graduated',
        ]);

        session([
            'student_portal_id' => $student->id,
            'student_portal_cnic' => $student->father_cnic,
            'student_portal_name' => $student->name,
        ]);

        Livewire::test(PortalDashboard::class)
            ->assertViewHas('isDeparted', true)
            ->assertViewHas('isGraduated', true)
            ->assertViewHas('isSlc', false)
            ->assertSee('Graduated Alumni')
            ->assertSee('Congratulations')
            ->assertSee('Class 10')
            ->assertDontSee('Weekly Timetable')
            ->assertDontSee('Today\'s Classes');
    }

    public function test_slc_issued_student_portal_shows_slc_notice_and_withholds_active_data(): void
    {
        $student = Student::create([
            'name' => 'Usman Tariq',
            'admission_no' => 'ADM-8888',
            'father_cnic' => '37405-8888888-1',
            'status' => 'withdrawn',
            'status_reason' => 'SLC Issued',
        ]);

        session([
            'student_portal_id' => $student->id,
            'student_portal_cnic' => $student->father_cnic,
            'student_portal_name' => $student->name,
        ]);

        Livewire::test(PortalDashboard::class)
            ->assertViewHas('isDeparted', true)
            ->assertViewHas('isGraduated', false)
            ->assertViewHas('isSlc', true)
            ->assertSee('School Leaving Certificate (SLC) Issued')
            ->assertDontSee('Weekly Timetable')
            ->assertDontSee('Today\'s Classes');
    }

    public function test_family_unit_sibling_switcher_with_graduated_and_active_siblings(): void
    {
        $cnic = '37405-5555555-1';

        $elderSibling = Student::create([
            'name' => 'Hamza Khan',
            'admission_no' => 'ADM-5001',
            'father_cnic' => $cnic,
            'status' => 'graduated',
        ]);

        $youngerSibling = Student::create([
            'name' => 'Bilal Khan',
            'admission_no' => 'ADM-5002',
            'father_cnic' => $cnic,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $youngerSibling->id,
            'class_id' => $this->class7->id,
            'academic_session_id' => $this->session2->id,
            'shift_type' => 'regular',
            'roll_number' => '5',
            'status' => 'active',
        ]);

        // Login as elder graduated sibling
        session([
            'student_portal_id' => $elderSibling->id,
            'student_portal_cnic' => $cnic,
            'student_portal_name' => $elderSibling->name,
        ]);

        $portal = Livewire::test(PortalDashboard::class)
            ->assertViewHas('isDeparted', true)
            ->assertViewHas('isGraduated', true)
            ->assertViewHas('hasActiveSiblings', true)
            ->assertSee('Hamza Khan')
            ->assertSee('Bilal Khan')
            ->assertSee('Graduated');

        // Switch to younger active sibling
        $portal->call('switchChild', $youngerSibling->id)
            ->assertViewHas('isDeparted', false)
            ->assertViewHas('isGraduated', false)
            ->assertSet('studentId', $youngerSibling->id)
            ->assertSee('Bilal');
    }

    public function test_student_manager_export_excel_with_proper_heading(): void
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'name' => 'Excel Test Student',
            'admission_no' => 'ADM-7777',
            'father_name' => 'Excel Father',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class10->id,
            'academic_session_id' => $this->session2->id,
            'shift_type' => 'regular',
            'roll_number' => '77',
            'status' => 'active',
        ]);

        $component = Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session2->id)
            ->call('exportExcel');

        $instance = $component->instance();
        $instance->selectedSessionId = $this->session2->id;
        $response = $instance->exportExcel();

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('students_export_2026-2027', $response->headers->get('Content-Disposition'));

        // Capture buffer output to verify proper institutional headings
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('OFFICIAL STUDENT DIRECTORY & ROSTER REPORT', $content);
        $this->assertStringContainsString('Academic Session: 2026-2027', $content);
        $this->assertStringContainsString('ADM-7777', $content);
        $this->assertStringContainsString('Excel Test Student', $content);
        $this->assertStringContainsString('Class 10', $content);
    }
}

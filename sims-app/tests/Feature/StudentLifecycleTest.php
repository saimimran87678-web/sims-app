<?php

namespace Tests\Feature;

use App\Livewire\Admin\StudentManager;
use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\FeePayment;
use App\Models\FeeRecord;
use App\Models\FeeWaiverApplication;
use App\Models\SchoolLeavingCertificate;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\LicenseStatus;
use App\Services\StudentLifecycleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicSession $session2025;
    private AcademicSession $session2026;
    private Classes $class7;
    private Classes $class8;
    private Section $sectionA;
    private StudentLifecycleService $lifecycleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session2025 = AcademicSession::create([
            'name' => 'Session 2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'shift_type' => 'Regular',
            'is_active' => false,
        ]);

        $this->session2026 = AcademicSession::create([
            'name' => 'Session 2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $this->class7 = Classes::create([
            'name' => 'Class 7',
            'numeric_value' => 7,
            'academic_session_id' => $this->session2025->id,
        ]);

        $this->class8 = Classes::create([
            'name' => 'Class 8',
            'numeric_value' => 8,
            'academic_session_id' => $this->session2026->id,
        ]);

        $this->sectionA = Section::create([
            'name' => 'A',
            'class_id' => $this->class7->id,
        ]);

        $this->lifecycleService = app(StudentLifecycleService::class);
    }

    public function test_student_with_financial_or_academic_records_cannot_be_hard_deleted(): void
    {
        $student = Student::create([
            'admission_no' => 'ADM-2025-001',
            'roll_no' => '101',
            'name' => 'Hamza Tariq',
            'b_form_number' => '35201-1111111-1',
            'father_name' => 'Tariq Mehmood',
            'father_cnic' => '35201-2222222-2',
            'gender' => 'Male',
            'dob' => '2012-05-15',
            'admission_date' => '2025-04-10',
            'original_admission_date' => '2025-04-10',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class7->id,
            'section_id' => $this->sectionA->id,
            'academic_session_id' => $this->session2025->id,
            'roll_no' => '101',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        FeeRecord::create([
            'student_id' => $student->id,
            'class_id' => $this->class7->id,
            'academic_session_id' => $this->session2025->id,
            'period' => '2025-05',
            'cycle' => 'monthly',
            'total_amount' => 4500.00,
            'paid_amount' => 0.00,
            'balance' => 4500.00,
            'status' => 'unpaid',
            'due_date' => '2025-05-10',
        ]);

        // Attempting forceDelete must throw DomainException
        try {
            $student->forceDelete();
            $this->fail('Expected DomainException was not thrown on forceDelete.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Institutional integrity violation', $e->getMessage());
        }

        // Reload clean student instance without lingering forceDeleting state
        $student = Student::findOrFail($student->id);

        // Soft delete must succeed and preserve DB rows
        $student->delete();
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
        $this->assertDatabaseHas('fee_records', ['student_id' => $student->id]);
    }

    public function test_bform_lookup_identifies_returning_alumni(): void
    {
        $student = Student::create([
            'admission_no' => 'ADM-2025-002',
            'roll_no' => '102',
            'name' => 'Ayesha Khan',
            'b_form_number' => '35202-3333333-3',
            'father_name' => 'Nawaz Khan',
            'father_cnic' => '35202-4444444-4',
            'gender' => 'Female',
            'dob' => '2013-08-20',
            'admission_date' => '2025-04-12',
            'original_admission_date' => '2025-04-12',
            'class_id' => $this->class7->id,
            'status' => 'withdrawn',
        ]);

        // Lookup by B-Form
        $alumniByBform = $this->lifecycleService->lookupAlumni('35202-3333333-3');
        $this->assertNotNull($alumniByBform);
        $this->assertEquals($student->id, $alumniByBform['id']);
        $this->assertEquals('Ayesha Khan', $alumniByBform['name']);
        $this->assertEquals('withdrawn', $alumniByBform['status']);

        // Lookup by Father CNIC
        $alumniByCnic = $this->lifecycleService->lookupAlumni('35202-4444444-4');
        $this->assertNotNull($alumniByCnic);
        $this->assertEquals($student->id, $alumniByCnic['id']);
    }

    public function test_slc_issuance_blocks_when_fees_enabled_and_balance_unpaid(): void
    {
        $student = Student::create([
            'admission_no' => 'ADM-2025-003',
            'roll_no' => '103',
            'name' => 'Bilal Ahmed',
            'b_form_number' => '35201-5555555-5',
            'father_name' => 'Ahmed Ali',
            'gender' => 'Male',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class7->id,
            'section_id' => $this->sectionA->id,
            'academic_session_id' => $this->session2025->id,
            'roll_no' => '103',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        FeeRecord::create([
            'student_id' => $student->id,
            'class_id' => $this->class7->id,
            'academic_session_id' => $this->session2025->id,
            'period' => '2025-06',
            'cycle' => 'monthly',
            'total_amount' => 5000.00,
            'paid_amount' => 0.00,
            'balance' => 5000.00,
            'status' => 'unpaid',
            'due_date' => '2025-06-10',
        ]);

        $clearance = $this->lifecycleService->checkFinancialClearance($student, $this->session2025->id);
        if (LicenseStatus::isModuleEnabled('fees')) {
            $this->assertFalse($clearance['cleared']);
            $this->assertEquals(5000.00, $clearance['outstanding_balance']);

            $this->expectException(\DomainException::class);
            $this->expectExceptionMessage('Cannot issue School Leaving Certificate');
            $this->lifecycleService->issueSchoolLeavingCertificate(
                $student,
                $enrollment,
                now(),
                'parent_relocation',
                'Exemplary',
                'Good student',
                $this->admin->id
            );
        } else {
            $this->assertTrue($clearance['cleared']);
        }
    }

    public function test_fee_waiver_clears_slc_block_and_adjusts_ledger(): void
    {
        $student = Student::create([
            'admission_no' => 'ADM-2025-004',
            'roll_no' => '104',
            'name' => 'Zainab Bibi',
            'b_form_number' => '35201-6666666-6',
            'father_name' => 'Bibi Lal',
            'gender' => 'Female',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class7->id,
            'section_id' => $this->sectionA->id,
            'academic_session_id' => $this->session2025->id,
            'roll_no' => '104',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        $feeRecord = FeeRecord::create([
            'student_id' => $student->id,
            'class_id' => $this->class7->id,
            'academic_session_id' => $this->session2025->id,
            'period' => '2025-06',
            'cycle' => 'monthly',
            'total_amount' => 3000.00,
            'paid_amount' => 0.00,
            'balance' => 3000.00,
            'status' => 'unpaid',
            'due_date' => '2025-06-10',
        ]);

        // Apply institutional fee waiver
        $waiver = $this->lifecycleService->applyFeeWaiver(
            $feeRecord,
            3000.00,
            'Principal',
            'Humanitarian waiver for SLC departure',
            'ORD-2026-99',
            $this->admin->id
        );

        $this->assertInstanceOf(FeeWaiverApplication::class, $waiver);
        $this->assertEquals(3000.00, $waiver->amount);

        // Check fee record is cleared
        $feeRecord->refresh();
        $this->assertEquals(0.00, (float) $feeRecord->balance);
        $this->assertEquals('paid', $feeRecord->status);

        // Financial clearance should now pass
        $clearance = $this->lifecycleService->checkFinancialClearance($student, $this->session2025->id);
        $this->assertTrue($clearance['cleared']);

        // Issue SLC
        $slc = $this->lifecycleService->issueSchoolLeavingCertificate(
            $student,
            $enrollment,
            now(),
            'parent_relocation',
            'Very Good',
            'Completed Class 7',
            $this->admin->id
        );

        $this->assertInstanceOf(SchoolLeavingCertificate::class, $slc);
        $this->assertStringStartsWith('SLC-', $slc->certificate_number);

        // Verify lifecycle state updates
        $student->refresh();
        $enrollment->refresh();
        $this->assertEquals('withdrawn', $student->status);
        $this->assertEquals('slc_issued', $enrollment->status);
    }

    public function test_re_admission_creates_new_enrollment_preserving_historical_sessions(): void
    {
        $origAdmissionDate = '2024-04-01';

        $student = Student::create([
            'admission_no' => 'ADM-2024-099',
            'roll_no' => '99',
            'name' => 'Usman Tariq',
            'b_form_number' => '35201-7777777-7',
            'father_name' => 'Tariq Jameel',
            'gender' => 'Male',
            'admission_date' => $origAdmissionDate,
            'original_admission_date' => $origAdmissionDate,
            'status' => 'withdrawn',
        ]);

        $oldEnrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class7->id,
            'section_id' => $this->sectionA->id,
            'academic_session_id' => $this->session2025->id,
            'roll_no' => '99',
            'shift_type' => 'regular',
            'status' => 'slc_issued',
        ]);

        // Re-admit to active session (2026-2027) in Class 8
        $sectionB8 = Section::create([
            'name' => 'B',
            'class_id' => $this->class8->id,
        ]);

        $newEnrollment = $this->lifecycleService->readmitStudent(
            $student,
            $this->class8,
            $sectionB8,
            'regular',
            '2026-04-15'
        );

        $student->refresh();
        $oldEnrollment->refresh();

        // Student status re-activated
        $this->assertEquals('active', $student->status);
        $this->assertEquals($origAdmissionDate, $student->original_admission_date->format('Y-m-d'));
        $this->assertEquals($this->class8->id, $student->class_id);

        // Historical enrollment untouched
        $this->assertEquals('slc_issued', $oldEnrollment->status);
        $this->assertEquals($this->session2025->id, $oldEnrollment->academic_session_id);

        // New enrollment active in Session 2026
        $this->assertEquals('active', $newEnrollment->status);
        $this->assertEquals($this->session2026->id, $newEnrollment->academic_session_id);
        $this->assertEquals($this->class8->id, $newEnrollment->class_id);
    }

    public function test_lifetime_portfolio_aggregates_360_degree_timeline(): void
    {
        $student = Student::create([
            'admission_no' => 'ADM-360-001',
            'roll_no' => '201',
            'name' => 'Fatima Noor',
            'b_form_number' => '35201-8888888-8',
            'father_name' => 'Noor Muhammad',
            'gender' => 'Female',
            'admission_date' => '2025-04-01',
            'original_admission_date' => '2025-04-01',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class7->id,
            'section_id' => $this->sectionA->id,
            'academic_session_id' => $this->session2025->id,
            'roll_no' => '201',
            'shift_type' => 'regular',
            'status' => 'completed',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class8->id,
            'section_id' => null,
            'academic_session_id' => $this->session2026->id,
            'roll_no' => '201',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        $portfolio = $this->lifecycleService->getLifetimePortfolio($student);

        $this->assertNotNull($portfolio);
        $this->assertEquals('Fatima Noor', $portfolio['student']['name']);
        $this->assertCount(2, $portfolio['sessions']);
        $this->assertArrayHasKey('attendance_rate', $portfolio);
        $this->assertArrayHasKey('exams_summary', $portfolio);
    }

    public function test_livewire_departure_wizard_and_history_modals(): void
    {
        $student = Student::create([
            'admission_no' => 'ADM-LW-001',
            'roll_no' => '301',
            'name' => 'Saad Rafique',
            'b_form_number' => '35201-9999999-9',
            'father_name' => 'Rafique Ahmed',
            'gender' => 'Male',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class8->id,
            'section_id' => null,
            'academic_session_id' => $this->session2026->id,
            'roll_no' => '301',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);

        $testable = Livewire::test(StudentManager::class)
            // Test 360 Lifetime History Modal
            ->call('openLifetimeHistory', $student->id)
            ->assertSet('showHistoryModal', true)
            ->assertSet('historyStudentId', $student->id)
            ->assertSee('Saad Rafique')
            ->assertSee('ADM-LW-001')
            ->assertSee('Sessions Enrolled')
            ->assertSee('Lifetime Attendance')
            ->assertSee('Academic Exams')
            ->assertSee('Institutional Session Audit Trail')
            ->assertSee('Print Portfolio')
            ->assertSee('Close Portfolio')
            ->call('closeHistoryModal')
            ->assertSet('showHistoryModal', false)

            // Test SLC Departure Modal
            ->call('startIssueSlc', $student->id)
            ->assertSet('showSlcModal', true)
            ->assertSet('slcStep', 1)
            ->call('advanceSlcStep')
            ->assertSet('slcStep', 2)
            ->set('slcLeavingDate', '2026-05-15')
            ->set('slcReason', 'parent_relocation')
            ->set('slcConduct', 'Exemplary')
            ->set('slcRemarks', 'Outstanding student')
            ->call('advanceSlcStep')
            ->assertSet('slcStep', 3)
            ->call('finalizeSlc');

        $this->assertNotNull($testable->get('issuedSlc'));

        $this->assertDatabaseHas('school_leaving_certificates', [
            'student_id' => $student->id,
            'reason_for_leaving' => 'parent_relocation',
            'conduct' => 'Exemplary',
        ]);
    }

    public function test_official_slc_printable_pdf_route(): void
    {
        $student = Student::create([
            'admission_no' => 'ADM-PDF-001',
            'roll_no' => '401',
            'name' => 'Maryam Nawaz',
            'b_form_number' => '35201-0000000-0',
            'father_name' => 'Muhammad Nawaz',
            'gender' => 'Female',
            'status' => 'withdrawn',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class8->id,
            'section_id' => null,
            'academic_session_id' => $this->session2026->id,
            'roll_no' => '401',
            'shift_type' => 'regular',
            'status' => 'slc_issued',
        ]);

        $slc = SchoolLeavingCertificate::create([
            'student_id' => $student->id,
            'academic_session_id' => $this->session2026->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'SLC-2026-9999',
            'leaving_date' => now(),
            'class_at_leaving' => 'Class 8',
            'reason_for_leaving' => 'course_completion',
            'conduct' => 'Exemplary',
            'fee_dues_cleared' => true,
            'issued_by_user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        // Test HTML format output
        $responseHtml = $this->get(route('admin.students.slc.print', ['certificate' => $slc->id, 'format' => 'html']));
        $responseHtml->assertStatus(200);
        $responseHtml->assertSee('School Leaving Certificate');
        $responseHtml->assertSee('SLC-2026-9999');
        $responseHtml->assertSee('Maryam Nawaz');

        // Test PDF stream output
        $responsePdf = $this->get(route('admin.students.slc.print', ['certificate' => $slc->id]));
        $responsePdf->assertStatus(200);
        $responsePdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_student_soft_deletion_and_one_click_restoration(): void
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'name' => 'Kashif Ali',
            'admission_no' => 'ADM-7711',
            'father_name' => 'Ali Ahmed',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class8->id,
            'academic_session_id' => $this->session2026->id,
            'roll_number' => '15',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        // Attach an attendance record so this student has audit history and triggers safe archival
        \Illuminate\Support\Facades\DB::table('attendances')->insert([
            'student_id' => $student->id,
            'date' => '2026-05-01',
            'status' => 'P',
            'academic_session_id' => $this->session2026->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Archive student with records from StudentManager
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session2026->id)
            ->call('delete', $student->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertSoftDeleted('enrollments', ['id' => $enrollment->id]);

        // 2. Query Archived filter in StudentManager
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session2026->id)
            ->set('filterStatus', 'archived')
            ->assertSee('Kashif Ali')
            ->assertSee('Archived Profile')
            ->assertSee('Restore')
            ->call('restoreStudent', $student->id)
            ->assertHasNoErrors()
            ->assertSee('has been restored successfully');

        // 3. Verify student and enrollment are fully restored
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'status' => 'active',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => 'active',
            'deleted_at' => null,
        ]);
    }

    public function test_fresh_student_with_no_records_is_permanently_deleted_directly(): void
    {
        $this->actingAs($this->admin);

        // A freshly created student with no attendance, fees, marks, or foreign sessions
        $student = Student::create([
            'name' => 'Brand New Student',
            'admission_no' => 'ADM-FRESH-001',
            'father_name' => 'Fresh Parent',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class8->id,
            'academic_session_id' => $this->session2026->id,
            'roll_number' => '99',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        // Deleting this fresh student should permanently purge them from the database
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session2026->id)
            ->call('delete', $student->id)
            ->assertHasNoErrors()
            ->assertSee('was permanently deleted');

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_defaulter_list_and_ledger_for_archived_student_with_unpaid_fees(): void
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'name' => 'Zubair Khan',
            'admission_no' => 'ADM-8822',
            'father_name' => 'Khan Mohammad',
            'phone' => '03001234567',
            'gender' => 'male',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class8->id,
            'academic_session_id' => $this->session2026->id,
            'roll_number' => '22',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        $feeRecord = FeeRecord::create([
            'student_id' => $student->id,
            'class_id' => $this->class8->id,
            'academic_session_id' => $this->session2026->id,
            'period' => now()->format('Y-m'),
            'total_amount' => 2500,
            'paid_amount' => 0,
            'balance' => 2500,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
            'cycle' => 'monthly',
        ]);

        // Soft delete the student (archive profile)
        $student->delete();
        $this->assertTrue($student->trashed());

        // 1. Defaulter List: Must resolve student details cleanly without "N/A"
        Livewire::test(\App\Livewire\Admin\Fee\DefaulterList::class)
            ->assertSee('Zubair Khan')
            ->assertSee('ADM-8822')
            ->assertSee('Archived Profile')
            ->assertSee('2,500.00');

        // 2. Student Ledger: Must resolve student withTrashed() and display ledger cleanly
        Livewire::test(\App\Livewire\Admin\Fee\StudentLedger::class, ['studentId' => $student->id])
            ->assertSee('Zubair Khan')
            ->assertSee('ADM-8822')
            ->assertSee('Archived Profile')
            ->assertSee('2,500.00');
    }
}

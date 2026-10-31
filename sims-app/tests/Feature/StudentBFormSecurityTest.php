<?php

namespace Tests\Feature;

use App\Livewire\Admin\StudentManager;
use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentBFormSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicSession $session;
    private Classes $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session = AcademicSession::create([
            'name' => 'Session 2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $this->class = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $this->session->id,
            'shift_type' => 'regular',
        ]);
    }

    public function test_student_bform_cannot_match_father_cnic(): void
    {
        $matchingCnic = '35202-1234567-1';

        Livewire::actingAs($this->admin)
            ->test(StudentManager::class)
            ->call('openModal')
            ->set('name', 'Ali Khan')
            ->set('father_name', 'Tariq Khan')
            ->set('b_form_number', $matchingCnic)
            ->set('father_cnic', $matchingCnic)
            ->assertHasErrors(['b_form_number', 'father_cnic'])
            ->call('save')
            ->assertHasErrors(['b_form_number', 'father_cnic']);

        $this->assertEquals(0, Student::count());
    }

    public function test_student_bform_cannot_match_guardian_cnic(): void
    {
        $matchingCnic = '35202-7654321-9';

        Livewire::actingAs($this->admin)
            ->test(StudentManager::class)
            ->call('openModal')
            ->set('name', 'Usman Khan')
            ->set('father_name', 'Tariq Khan')
            ->set('b_form_number', $matchingCnic)
            ->set('father_cnic', '35202-1111111-1')
            ->set('is_guardian_primary', true)
            ->set('guardian_cnic', $matchingCnic)
            ->assertHasErrors(['b_form_number', 'guardian_cnic'])
            ->call('save')
            ->assertHasErrors(['b_form_number', 'guardian_cnic']);

        $this->assertEquals(0, Student::count());
    }

    public function test_bform_lookup_exclusively_checks_student_and_locks_existing_records(): void
    {
        $bForm = '35201-9876543-1';
        $fatherCnic = '35201-1122334-5';

        $existing = Student::create([
            'admission_no' => 'ADM-ORIGINAL-001',
            'roll_no' => '55',
            'name' => 'Hamza Tariq',
            'father_name' => 'Tariq Mahmood',
            'b_form_number' => $bForm,
            'father_cnic' => $fatherCnic,
            'gender' => 'Male',
            'dob' => '2010-05-15',
            'phone' => '03001234567',
            'address' => 'House 123, Lahore',
            'status' => 'graduated',
            'status_reason' => 'Graduated matriculation',
            'academic_session_id' => $this->session->id,
            'class_id' => $this->class->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(StudentManager::class)
            ->call('openModal')
            ->set('b_form_number', $bForm)
            ->assertSet('isExistingStudentRecord', true)
            ->assertSet('name', 'Hamza Tariq')
            ->assertSet('father_name', 'Tariq Mahmood')
            ->assertSet('father_cnic', $fatherCnic)
            ->assertSet('admission_no', 'ADM-ORIGINAL-001')
            ->assertSet('phone', '03001234567')
            ->assertSet('address', 'House 123, Lahore');
    }

    public function test_father_cnic_does_not_populate_student_data_exclusively_finds_siblings(): void
    {
        $fatherCnic = '35201-9988776-5';

        // Existing sibling
        $sibling = Student::create([
            'admission_no' => 'ADM-SIBLING-001',
            'roll_no' => '12',
            'name' => 'Zainab Tariq',
            'father_name' => 'Tariq Mehmood',
            'b_form_number' => '35201-0000001-1',
            'father_cnic' => $fatherCnic,
            'gender' => 'Female',
            'status' => 'active',
            'academic_session_id' => $this->session->id,
            'class_id' => $this->class->id,
        ]);

        // When entering new admission, typing Father CNIC should find sibling but NOT fill student name or lock record
        Livewire::actingAs($this->admin)
            ->test(StudentManager::class)
            ->call('openModal')
            ->set('father_cnic', $fatherCnic)
            ->assertSet('isExistingStudentRecord', false)
            ->assertSet('name', '') // Must remain blank, NOT populated from sibling
            ->assertCount('detectedSiblings', 1);
    }

    public function test_clearing_or_changing_bform_unlocks_fields(): void
    {
        $bForm = '35201-4455667-1';

        Student::create([
            'admission_no' => 'ADM-LOCK-001',
            'roll_no' => '77',
            'name' => 'Bilal Ahmed',
            'father_name' => 'Ahmed Khan',
            'b_form_number' => $bForm,
            'father_cnic' => '35201-8899001-2',
            'gender' => 'Male',
            'status' => 'withdrawn',
            'academic_session_id' => $this->session->id,
            'class_id' => $this->class->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(StudentManager::class)
            ->call('openModal')
            ->set('b_form_number', $bForm)
            ->assertSet('isExistingStudentRecord', true)
            ->assertSet('name', 'Bilal Ahmed')
            ->call('clearExistingStudentRecordLock', false)
            ->assertSet('isExistingStudentRecord', false)
            ->assertSet('name', '')
            ->assertSet('b_form_number', '');
    }

    public function test_saving_admission_with_existing_bform_readmits_without_creating_duplicate_row(): void
    {
        $bForm = '35201-7788990-3';

        $existing = Student::create([
            'admission_no' => 'ADM-HISTORIC-999',
            'roll_no' => '88',
            'name' => 'Khadija Bibi',
            'father_name' => 'Muhammad Aslam',
            'b_form_number' => $bForm,
            'father_cnic' => '35201-1112223-4',
            'gender' => 'Female',
            'status' => 'withdrawn',
            'academic_session_id' => $this->session->id,
            'class_id' => $this->class->id,
        ]);

        $this->assertEquals(1, Student::count());

        Livewire::actingAs($this->admin)
            ->test(StudentManager::class)
            ->call('openModal')
            ->set('b_form_number', $bForm)
            ->set('class_id', $this->class->id)
            ->set('roll_no', '99')
            ->call('save')
            ->assertHasNoErrors();

        // Ensure no duplicate student was inserted in students table
        $this->assertEquals(1, Student::count());

        $existing->refresh();
        $this->assertEquals('active', $existing->status);
        $this->assertEquals('ADM-HISTORIC-999', $existing->admission_no);

        // Check new enrollment in current session was created
        $enrollment = Enrollment::where('student_id', $existing->id)
            ->where('academic_session_id', $this->session->id)
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertEquals('99', $enrollment->roll_number);
        $this->assertEquals('active', $enrollment->status);
    }

    public function test_active_student_bform_detects_duplicate_and_blocks_admission(): void
    {
        $bForm = '35201-5555555-1';

        $activeStudent = Student::create([
            'admission_no' => 'ADM-ACTIVE-101',
            'roll_no' => '10',
            'name' => 'Muhammad Saim',
            'father_name' => 'Imran Khan',
            'b_form_number' => $bForm,
            'father_cnic' => '35201-9999999-1',
            'gender' => 'Male',
            'status' => 'active',
            'academic_session_id' => $this->session->id,
            'class_id' => $this->class->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(StudentManager::class)
            ->call('openModal')
            ->set('b_form_number', $bForm)
            // Assert Active Duplicate Flags
            ->assertSet('isExistingStudentRecord', true)
            ->assertSet('isActiveStudentDuplicate', true)
            ->assertSet('isReadmission', false)
            ->assertSet('readmitStudentId', null)
            ->assertSet('detectedAlumni', null)
            ->assertSet('name', 'Muhammad Saim')
            // Assert active details captured
            ->assertSet('activeStudentDetails.admission_no', 'ADM-ACTIVE-101')
            ->assertSet('activeStudentDetails.roll_no', '10')
            ->assertSet('activeStudentDetails.class_name', 'Class 9th')
            // Verify error on b_form_number
            ->assertHasErrors(['b_form_number'])
            // Attempting to call save should be completely blocked
            ->call('save')
            ->assertHasErrors(['b_form_number']);

        // Assert no new student or duplicate was created
        $this->assertEquals(1, Student::count());
    }

    public function test_admission_date_is_required_for_student_creation(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StudentManager::class)
            ->call('openModal')
            ->set('name', 'Fatima Zahra')
            ->set('b_form_number', '35201-9988112-2')
            ->set('father_name', 'Zahoor Ahmed')
            ->set('father_cnic', '35201-3344556-7')
            ->set('class_id', $this->class->id)
            ->set('roll_no', '15')
            ->set('admission_no', 'ADM-REQ-001')
            ->set('admission_date', '')
            ->call('save')
            ->assertHasErrors(['admission_date']);
    }
}

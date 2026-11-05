<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\FeeRecord;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\Setting;
use App\Services\StudentLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DatabaseInvariantsAndModelDecouplingTest extends TestCase
{
    use RefreshDatabase;

    protected $session;
    protected $class;
    protected $subject1;
    protected $subject2;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->session = AcademicSession::create([
            'name' => '2026-2027 Invariant Test Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $this->class = Classes::create([
            'academic_session_id' => $this->session->id,
            'name' => 'Class 9-A',
            'numeric_value' => 9,
            'shift_type' => 'regular',
            'tuition_fee' => 2500,
        ]);

        $this->subject1 = Subject::firstOrCreate(
            ['name' => 'Physics'],
            ['code' => 'PHY', 'category' => 'Science', 'is_active' => true]
        );

        $this->subject2 = Subject::firstOrCreate(
            ['name' => 'Biology'],
            ['code' => 'BIO', 'category' => 'Science', 'is_active' => true]
        );
    }

    public function test_student_creation_is_decoupled_from_global_http_request()
    {
        // Simulate arbitrary global request parameters that legacy code might have read
        request()->merge([
            'class_id' => 99999,
            'roll_no' => 'LEAKED-ROLL-99',
            'shift_type' => 'evening',
        ]);

        $student = Student::create([
            'name' => 'Decoupled Student',
            'admission_no' => 'ADM-DEC-001',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $this->assertNotNull($student->id);

        // Verify that NO enrollment was implicitly or secretly created with the leaked request params
        $enrollmentsCount = Enrollment::where('student_id', $student->id)->count();
        $this->assertEquals(0, $enrollmentsCount, 'Student creation must not read global request() or create implicit enrollments.');

        $bogusEnrollment = Enrollment::where('class_id', 99999)->first();
        $this->assertNull($bogusEnrollment);
    }

    public function test_student_lifecycle_service_creates_student_and_enrollment_atomically()
    {
        $service = new StudentLifecycleService();

        $result = $service->createStudentWithEnrollment(
            [
                'name' => 'Atomic Student',
                'admission_no' => 'ADM-ATM-001',
                'gender' => 'female',
                'admission_date' => '2026-02-01',
                'dob' => '2012-05-15',
            ],
            $this->class->id,
            'ROLL-01',
            null,
            'regular',
            $this->session->id
        );

        $student = $result['student'];
        $enrollment = $result['enrollment'];

        $this->assertInstanceOf(Student::class, $student);
        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals($student->id, $enrollment->student_id);
        $this->assertEquals($this->class->id, $enrollment->class_id);
        $this->assertEquals('ROLL-01', $enrollment->roll_number);
        $this->assertEquals('regular', $enrollment->shift_type);
        $this->assertEquals($this->session->id, $enrollment->academic_session_id);
    }

    public function test_check_constraint_rejects_invalid_date_of_birth()
    {
        // DOB cannot be equal to or after admission date
        $this->expectException(QueryException::class);

        Student::create([
            'name' => 'Time Traveler Student',
            'admission_no' => 'ADM-TT-001',
            'dob' => '2026-05-01',
            'admission_date' => '2026-01-01',
            'gender' => 'male',
            'status' => 'active',
        ]);
    }

    public function test_check_constraint_allows_valid_date_of_birth()
    {
        $student = Student::create([
            'name' => 'Valid DOB Student',
            'admission_no' => 'ADM-VAL-001',
            'dob' => '2015-05-01',
            'admission_date' => '2026-01-01',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $this->assertNotNull($student->id);
    }

    public function test_check_constraint_rejects_invalid_student_status()
    {
        $this->expectException(QueryException::class);

        Student::create([
            'name' => 'Bad Status Student',
            'admission_no' => 'ADM-BAD-001',
            'status' => 'random_illegal_status',
        ]);
    }

    public function test_check_constraint_rejects_invalid_student_gender()
    {
        $this->expectException(QueryException::class);

        Student::create([
            'name' => 'Bad Gender Student',
            'admission_no' => 'ADM-BAD-002',
            'gender' => 'unknown_alien',
        ]);
    }

    public function test_check_constraint_rejects_invalid_student_card_status()
    {
        $this->expectException(QueryException::class);

        Student::create([
            'name' => 'Bad Card Student',
            'admission_no' => 'ADM-BAD-003',
            'card_status' => 'destroyed',
        ]);
    }

    public function test_check_constraint_rejects_invalid_enrollment_shift_and_status()
    {
        $student = Student::create([
            'name' => 'Shift Check Student',
            'admission_no' => 'ADM-SFT-001',
            'gender' => 'male',
        ]);

        // Invalid shift type
        try {
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $this->class->id,
                'academic_session_id' => $this->session->id,
                'shift_type' => 'graveyard_night',
                'status' => 'active',
            ]);
            $this->fail('Expected QueryException for invalid shift_type');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }

        // Invalid enrollment status
        try {
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $this->class->id,
                'academic_session_id' => $this->session->id,
                'shift_type' => 'morning',
                'status' => 'nonexistent_status',
            ]);
            $this->fail('Expected QueryException for invalid enrollment status');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_check_constraint_enforces_fee_financial_integrity()
    {
        $student = Student::create([
            'name' => 'Fee Invariant Student',
            'admission_no' => 'ADM-FEE-001',
            'gender' => 'male',
        ]);

        // 1. Mismatched balance: total 5000, paid 1000, but balance given as 3000 (should be 4000)
        try {
            FeeRecord::create([
                'student_id' => $student->id,
                'class_id' => $this->class->id,
                'academic_session_id' => $this->session->id,
                'period' => '2026-03',
                'cycle' => 'monthly',
                'total_amount' => 5000,
                'paid_amount' => 1000,
                'balance' => 3000, // Invalid: 5000 - 1000 = 4000
                'status' => 'partial',
                'due_date' => '2026-03-10',
            ]);
            $this->fail('Expected QueryException when balance does not equal total_amount - paid_amount');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }

        // 2. Negative total_amount
        try {
            FeeRecord::create([
                'student_id' => $student->id,
                'class_id' => $this->class->id,
                'academic_session_id' => $this->session->id,
                'period' => '2026-04',
                'cycle' => 'monthly',
                'total_amount' => -500,
                'paid_amount' => 0,
                'balance' => -500,
                'status' => 'unpaid',
                'due_date' => '2026-04-10',
            ]);
            $this->fail('Expected QueryException for negative total_amount');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }

        // 3. Valid fee record passes
        $validRecord = FeeRecord::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-05',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 1000,
            'balance' => 4000,
            'status' => 'partial',
            'due_date' => '2026-05-10',
        ]);

        $this->assertNotNull($validRecord->id);
        $this->assertEquals(4000, $validRecord->balance);
    }

    public function test_timetable_slot_uniqueness_prevents_slot_collision()
    {
        // 1. Single period slot
        Timetable::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject1->id,
            'day' => 'monday',
            'period_no' => 1,
            'is_divided' => false,
        ]);

        // Attempting to book the same class, day, and period for an undivided slot fails
        try {
            Timetable::create([
                'class_id' => $this->class->id,
                'subject_id' => $this->subject2->id,
                'day' => 'monday',
                'period_no' => 1,
                'is_divided' => false,
            ]);
            $this->fail('Expected QueryException for double booked single slot in timetable');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }

        // 2. Divided period slot (allows multiple elective subjects simultaneously)
        $slot1 = Timetable::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject1->id,
            'day' => 'tuesday',
            'period_no' => 2,
            'is_divided' => true,
        ]);

        $slot2 = Timetable::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject2->id,
            'day' => 'tuesday',
            'period_no' => 2,
            'is_divided' => true,
        ]);

        $this->assertNotNull($slot1->id);
        $this->assertNotNull($slot2->id);

        // But duplicate booking of the SAME subject in the same divided slot fails
        try {
            Timetable::create([
                'class_id' => $this->class->id,
                'subject_id' => $this->subject1->id,
                'day' => 'tuesday',
                'period_no' => 2,
                'is_divided' => true,
            ]);
            $this->fail('Expected QueryException for duplicate subject in divided timetable slot');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }
}

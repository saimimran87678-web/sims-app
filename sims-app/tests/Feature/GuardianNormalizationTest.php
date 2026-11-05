<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\FeeRecord;
use App\Models\FeePayment;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Livewire\Livewire;
use App\Livewire\Student\PortalDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class GuardianNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected $session;
    protected $class;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        // Ensure active academic session exists
        $this->session = AcademicSession::create([
            'name' => '2026-2027 Normalization Test Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $this->class = Classes::create([
            'academic_session_id' => $this->session->id,
            'name' => 'Class 10-A',
            'numeric_value' => 10,
            'shift_type' => 'regular',
            'tuition_fee' => 3000,
        ]);
    }

    public function test_student_creation_automatically_synchronizes_normalized_guardian_and_pivot()
    {
        $student = Student::create([
            'name' => 'Backfill Verify Student',
            'admission_no' => 'ADM-BF-001',
            'father_name' => 'Tariq Mehmood',
            'father_cnic' => '35201-1111111-1',
            'phone' => '03001234567',
            'gender' => 'male',
            'status' => 'active',
            'admission_date' => '2026-01-10',
        ]);

        $this->assertNotEmpty($student->guardians);
        $primary = $student->primaryGuardian;
        $this->assertNotNull($primary);
        $this->assertEquals('Tariq Mehmood', $primary->name);
        $this->assertEquals('35201-1111111-1', $primary->cnic);
        $this->assertEquals(1, $primary->pivot->is_primary);
        $this->assertTrue($primary->whatsapp_opt_in);
    }

    public function test_multiple_students_share_the_same_guardian_and_form_family_unit()
    {
        $child1 = Student::create([
            'name' => 'Zayd Khan',
            'admission_no' => 'ADM-ZK-001',
            'father_name' => 'Rashid Khan',
            'father_cnic' => '35201-7777777-1',
            'phone' => '03009998877',
            'gender' => 'male',
            'status' => 'active',
            'admission_date' => '2026-01-05',
        ]);

        $child2 = Student::create([
            'name' => 'Ayla Khan',
            'admission_no' => 'ADM-AK-002',
            'father_name' => 'Rashid Khan',
            'father_cnic' => '35201-7777777-1',
            'phone' => '03009998877',
            'gender' => 'female',
            'status' => 'active',
            'admission_date' => '2026-02-15',
        ]);

        // Both should share the exact same normalized guardian record by cnic_hash
        $guardian = Guardian::where('cnic_hash', Student::hashBlindIndex('35201-7777777-1'))->first();
        $this->assertNotNull($guardian);

        // Verify guardian->students collection has both children
        $guardianChildren = $guardian->fresh()->students;
        $this->assertCount(2, $guardianChildren);
        $this->assertTrue($guardianChildren->contains('id', $child1->id));
        $this->assertTrue($guardianChildren->contains('id', $child2->id));

        // Sibling relationship lookup
        $child1Siblings = $child1->fresh()->siblings()->get();
        $this->assertCount(1, $child1Siblings);
        $this->assertEquals($child2->id, $child1Siblings->first()->id);

        // Family unit resolution and ranking
        $familyUnit = $child1->fresh()->family_unit;
        $this->assertCount(2, $familyUnit);
        $this->assertEquals($child1->id, $familyUnit[0]->id); // Admitted Jan 5
        $this->assertEquals($child2->id, $familyUnit[1]->id); // Admitted Feb 15

        $this->assertEquals(0, $child1->fresh()->sibling_rank);
        $this->assertEquals(1, $child2->fresh()->sibling_rank);
    }

    public function test_virtual_backward_compatible_accessors_resolve_through_primary_guardian()
    {
        $guardian = Guardian::create([
            'name' => 'Dr. Salman Ahmed',
            'cnic' => '35201-8888888-1',
            'phone' => '03214445566',
            'whatsapp_opt_in' => true,
        ]);

        $student = Student::create([
            'name' => 'Bilal Salman',
            'admission_no' => 'ADM-BS-003',
            'gender' => 'male',
            'status' => 'active',
            'admission_date' => '2026-03-01',
        ]);

        // Attach guardian
        DB::table('student_guardian')->insert([
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'relation' => 'Father',
            'is_primary' => true,
            'is_emergency' => true,
            'can_pickup' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Null out direct legacy columns on student record to verify pure virtual accessor resolution
        DB::table('students')->where('id', $student->id)->update([
            'father_name' => null,
            'father_cnic' => null,
            'phone' => null,
        ]);

        $reloaded = Student::with('guardians')->find($student->id);

        $this->assertEquals('Dr. Salman Ahmed', $reloaded->father_name);
        $this->assertEquals('35201-8888888-1', $reloaded->father_cnic);
        $this->assertEquals('03214445566', $reloaded->phone);
    }

    public function test_whatsapp_opt_in_consent_skips_attendance_notifications()
    {
        $guardianOptIn = Guardian::create([
            'name' => 'Consent Parent Yes',
            'cnic' => '35201-2222222-1',
            'phone' => '03001112233',
            'whatsapp_opt_in' => true,
        ]);

        $guardianOptOut = Guardian::create([
            'name' => 'Consent Parent No',
            'cnic' => '35201-3333333-1',
            'phone' => '03009990011',
            'whatsapp_opt_in' => false,
        ]);

        $studentIn = Student::create([
            'name' => 'OptIn Student',
            'admission_no' => 'ADM-OPT-01',
            'father_cnic' => '35201-2222222-1',
            'phone' => '03001112233',
            'gender' => 'male',
            'status' => 'active',
            'admission_date' => '2026-01-01',
        ]);

        $studentOut = Student::create([
            'name' => 'OptOut Student',
            'admission_no' => 'ADM-OPT-02',
            'father_cnic' => '35201-3333333-1',
            'phone' => '03009990011',
            'gender' => 'female',
            'status' => 'active',
            'admission_date' => '2026-01-01',
        ]);

        $whatsapp = new WhatsAppService();

        $studentsPayload = [
            ['id' => $studentIn->id, 'phone' => '03001112233', 'name' => 'OptIn Student', 'roll_no' => '101', 'gender' => 'male'],
            ['id' => $studentOut->id, 'phone' => '03009990011', 'name' => 'OptOut Student', 'roll_no' => '102', 'gender' => 'female'],
        ];

        $result = $whatsapp->sendAttendanceNotifications($studentsPayload, 'A', '2026-11-05');

        // Verify OptIn student was queued (sent = 1)
        $this->assertEquals(1, $result['sent']);

        // Verify OptOut student was skipped
        $this->assertNotEmpty($result['skipped']);
        $this->assertStringContainsString('opted out', strtolower(implode(' ', $result['skipped'])));

        // Check database queue: only OptIn student is queued
        $inQueue = DB::table('whatsapp_queue')->where('student_id', $studentIn->id)->exists();
        $outQueue = DB::table('whatsapp_queue')->where('student_id', $studentOut->id)->exists();

        $this->assertTrue($inQueue);
        $this->assertFalse($outQueue);
    }

    public function test_whatsapp_opt_in_consent_skips_fee_payment_and_reminders()
    {
        $guardian = Guardian::create([
            'name' => 'Fee OptOut Guardian',
            'cnic' => '35201-4444444-1',
            'phone' => '03335556677',
            'whatsapp_opt_in' => false,
        ]);

        $student = Student::create([
            'name' => 'Fee Muted Student',
            'admission_no' => 'ADM-FEE-01',
            'father_cnic' => '35201-4444444-1',
            'phone' => '03335556677',
            'gender' => 'male',
            'status' => 'active',
            'admission_date' => '2026-01-01',
        ]);

        $feeRecord = FeeRecord::create([
            'student_id' => $student->id,
            'academic_session_id' => $this->session->id,
            'class_id' => $this->class->id,
            'cycle' => 'monthly',
            'period' => '2026-11',
            'due_date' => '2026-11-20',
            'total_amount' => 4500,
            'paid_amount' => 0,
            'balance' => 4500,
            'status' => 'unpaid',
        ]);

        $whatsapp = new WhatsAppService();

        // 1. Fee Reminders: Should skip opted-out guardian
        $result = $whatsapp->sendFeeReminders([$feeRecord]);
        $this->assertEquals(0, $result['sent']);

        // 2. Payment Notification: Should skip opted-out guardian
        $payment = new FeePayment([
            'student_id' => $student->id,
            'fee_record_id' => $feeRecord->id,
            'amount' => 2000,
            'payment_date' => now(),
            'payment_method' => 'cash',
        ]);
        $payment->setRelation('student', $student);
        $payment->setRelation('record', $feeRecord);

        $whatsapp->sendPaymentNotification($payment);

        $queued = DB::table('whatsapp_queue')->where('student_id', $student->id)->exists();
        $this->assertFalse($queued);
    }

    public function test_portal_dashboard_allows_parent_to_toggle_whatsapp_consent()
    {
        $guardian = Guardian::create([
            'name' => 'Portal Toggle Parent',
            'cnic' => '35201-6666666-1',
            'phone' => '03004443322',
            'whatsapp_opt_in' => true,
        ]);

        $student = Student::create([
            'name' => 'Portal Child',
            'admission_no' => 'ADM-PC-01',
            'father_cnic' => '35201-6666666-1',
            'phone' => '03004443322',
            'gender' => 'male',
            'status' => 'active',
            'admission_date' => '2026-01-01',
        ]);

        session([
            'student_portal_id' => $student->id,
            'student_portal_name' => $student->name,
            'student_portal_cnic' => '35201-6666666-1',
        ]);

        // Mount portal dashboard and toggle opt-in
        Livewire::test(PortalDashboard::class)
            ->assertSet('whatsappOptIn', true)
            ->call('toggleWhatsAppOptIn')
            ->assertSet('whatsappOptIn', false)
            ->assertSee('WhatsApp notification alerts muted');

        $this->assertFalse((bool) $guardian->fresh()->whatsapp_opt_in);

        // Toggle back
        Livewire::test(PortalDashboard::class)
            ->call('toggleWhatsAppOptIn')
            ->assertSet('whatsappOptIn', true)
            ->assertSee('WhatsApp notification alerts enabled');

        $this->assertTrue((bool) $guardian->fresh()->whatsapp_opt_in);
    }

    public function test_process_whatsapp_queue_cancels_message_if_parent_opted_out_prior_to_sending()
    {
        $guardian = Guardian::create([
            'name' => 'Queue OptOut Guardian',
            'cnic' => '35201-9999999-1',
            'phone' => '03001239999',
            'whatsapp_opt_in' => false,
        ]);

        $student = Student::create([
            'name' => 'Queue OptOut Child',
            'admission_no' => 'ADM-QO-01',
            'father_cnic' => '35201-9999999-1',
            'phone' => '03001239999',
            'gender' => 'male',
            'status' => 'active',
            'admission_date' => '2026-01-01',
        ]);

        $queueId = DB::table('whatsapp_queue')->insertGetId([
            'phone' => '03001239999',
            'message' => 'Pending attendance alert',
            'status' => 'pending',
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run queue processor in --once mode
        $this->artisan('whatsapp:process-queue', ['--once' => true])
            ->assertSuccessful();

        $updatedQueueItem = DB::table('whatsapp_queue')->find($queueId);
        $this->assertEquals('failed', $updatedQueueItem->status);
        $this->assertStringContainsString('Guardian opted out', $updatedQueueItem->error_message);
    }
}

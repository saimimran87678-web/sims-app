<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Student;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\FeeRecord;
use App\Models\FeePayment;
use App\Services\FeePaymentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use App\Livewire\Admin\Fee\StudentLedger;
use App\Livewire\Admin\Fee\RecordPayment;

class FeeLedgerImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AcademicSession $session;
    protected Classes $class;
    protected Student $student;
    protected FeePaymentService $feeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('Super Admin');
        $this->actingAs($this->admin);

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $this->class = Classes::create([
            'name' => 'Class 10th',
            'numeric_value' => 10,
            'academic_session_id' => $this->session->id,
            'shift_type' => 'regular',
        ]);

        $this->student = Student::create([
            'name' => 'Ahmad Khan',
            'admission_no' => 'ADM-2026-001',
            'roll_no' => '101',
            'class_id' => $this->class->id,
            'gender' => 'male',
            'status' => 'active',
        ]);

        \App\Models\Setting::clearRuntimeCache();
        \Illuminate\Support\Facades\Cache::flush();

        $this->feeService = app(FeePaymentService::class);
    }

    public function test_payment_generates_sequential_receipt_number()
    {
        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 10000,
            'paid_amount' => 0,
            'balance' => 10000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $p1 = $this->feeService->recordPayment(
            record: $record,
            amount: 3000,
            paymentMethod: 'cash',
            receivedByUserId: $this->admin->id
        );

        $p2 = $this->feeService->recordPayment(
            record: $record,
            amount: 2000,
            paymentMethod: 'bank_transfer',
            receivedByUserId: $this->admin->id
        );

        $year = date('Y');
        $this->assertEquals("REC-{$year}-00001", $p1->receipt_number);
        $this->assertEquals("REC-{$year}-00002", $p2->receipt_number);
        $this->assertEquals($this->admin->id, $p1->received_by_user_id);
    }

    public function test_fee_record_recalculate_balances_ssot()
    {
        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 8000,
            'paid_amount' => 0,
            'balance' => 8000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        // First installment: 3,000
        $this->feeService->recordPayment($record, 3000);
        $record->refresh();
        $this->assertEquals(3000, (float) $record->paid_amount);
        $this->assertEquals(5000, (float) $record->balance);
        $this->assertEquals('partial', $record->status);

        // Second installment: 5,000
        $this->feeService->recordPayment($record, 5000);
        $record->refresh();
        $this->assertEquals(8000, (float) $record->paid_amount);
        $this->assertEquals(0, (float) $record->balance);
        $this->assertEquals('paid', $record->status);
    }

    public function test_superadmin_can_void_payment_with_reason_and_balance_restores()
    {
        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $payment = $this->feeService->recordPayment($record, 5000, 'cash');
        $record->refresh();
        $this->assertEquals('paid', $record->status);
        $this->assertEquals(0, (float) $record->balance);

        // Now Void the payment
        $this->feeService->voidPayment(
            paymentId: $payment->id,
            reason: 'Entered Wrong Amount by cashier',
            authorizerUserId: $this->admin->id
        );

        $payment->refresh();
        $this->assertTrue((bool) $payment->is_void);
        $this->assertEquals('Entered Wrong Amount by cashier', $payment->void_reason);
        $this->assertEquals($this->admin->id, $payment->voided_by_user_id);
        $this->assertNotNull($payment->voided_at);

        // Bill balance must be restored
        $record->refresh();
        $this->assertEquals(0, (float) $record->paid_amount);
        $this->assertEquals(5000, (float) $record->balance);
        $this->assertEquals('unpaid', $record->status);
    }

    public function test_regular_user_cannot_void_payment_without_authorization()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $payment = $this->feeService->recordPayment($record, 2000);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->feeService->voidPayment(
            paymentId: $payment->id,
            reason: 'Unauthorized test',
            authorizerUserId: $teacher->id
        );
    }

    public function test_universal_fee_ledger_switches_students_and_renders_running_balance()
    {
        $student2 = Student::create([
            'name' => 'Bilal Tariq',
            'admission_no' => 'ADM-2026-002',
            'roll_no' => '102',
            'class_id' => $this->class->id,
            'gender' => 'male',
            'status' => 'active',
        ]);

        // Invoice 1: 5,000
        $rec1 = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-07',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-07-10',
        ]);

        // Payment 1: 3,000
        $this->feeService->recordPayment($rec1, 3000);

        // Test Livewire component
        $component = Livewire::test(StudentLedger::class, ['studentId' => $this->student->id]);
        $component->assertSee('Universal Fee Ledger');
        $component->assertSee('Ahmad Khan');
        $component->assertSee('Rs. 5,000.00'); // Total Invoiced
        $component->assertSee('Rs. 3,000.00'); // Total Paid
        $component->assertSee('Rs. 2,000.00'); // Outstanding Balance

        // Test student switcher
        $component->call('selectStudent', $student2->id);
        $component->assertSee('Bilal Tariq');
    }

    public function test_daily_collection_summary_reconciliation()
    {
        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 15000,
            'paid_amount' => 0,
            'balance' => 15000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $p1 = $this->feeService->recordPayment($record, 4000, 'cash', receivedByUserId: $this->admin->id);
        $p2 = $this->feeService->recordPayment($record, 6000, 'bank_transfer', receivedByUserId: $this->admin->id);
        $p3 = $this->feeService->recordPayment($record, 2000, 'cash', receivedByUserId: $this->admin->id);

        // Void p3
        $this->feeService->voidPayment($p3->id, 'Clerk test mistake', $this->admin->id);

        $summary = $this->feeService->getDailyCollectionSummary(date('Y-m-d'));

        // Net collected should be p1 (4000) + p2 (6000) = 10,000 (excluding p3)
        $this->assertEquals(10000, $summary['totalCollected']);
        $this->assertEquals(4000, $summary['cashCollected']);
        $this->assertEquals(6000, $summary['bankCollected']);
        $this->assertEquals(2000, $summary['voidedTotal']);
        $this->assertEquals(2, $summary['validCount']);
        $this->assertEquals(1, $summary['voidedCount']);
    }

    public function test_cashier_can_submit_void_request_into_pending_queue()
    {
        $cashier = User::factory()->create(['role' => 'admin']);
        $cashier->givePermissionTo('fees.manage');

        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $payment = $this->feeService->recordPayment($record, 2000, receivedByUserId: $cashier->id);

        $this->feeService->requestPaymentVoid(
            paymentId: $payment->id,
            reason: 'Wrong student selected by cashier',
            requesterUserId: $cashier->id
        );

        $payment->refresh();
        $this->assertFalse($payment->is_void);
        $this->assertEquals('pending', $payment->void_status);
        $this->assertEquals($cashier->id, $payment->void_requested_by_user_id);
        $this->assertNotNull($payment->void_requested_at);
        $this->assertEquals('Wrong student selected by cashier', $payment->void_reason);

        // Verify balance not yet changed while pending
        $record->refresh();
        $this->assertEquals(3000, $record->balance);

        // Verify it appears in pending requests list
        $pending = $this->feeService->getPendingVoidRequests();
        $this->assertCount(1, $pending);
        $this->assertEquals($payment->id, $pending->first()->id);
    }

    public function test_superadmin_can_authorize_pending_void_request()
    {
        $cashier = User::factory()->create(['role' => 'admin']);
        $cashier->givePermissionTo('fees.manage');

        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $payment = $this->feeService->recordPayment($record, 2000, receivedByUserId: $cashier->id);

        // Submit void request
        $this->feeService->requestPaymentVoid($payment->id, 'Mistake entered', $cashier->id);

        // Superadmin authorizes
        $this->feeService->authorizePendingVoid(
            paymentId: $payment->id,
            authorizerUserId: $this->admin->id
        );

        $payment->refresh();
        $this->assertTrue($payment->is_void);
        $this->assertEquals('approved', $payment->void_status);
        $this->assertEquals($this->admin->id, $payment->voided_by_user_id);

        // Record balance restored
        $record->refresh();
        $this->assertEquals(5000, $record->balance);
        $this->assertEquals(0, $record->paid_amount);
        $this->assertEquals('unpaid', $record->status);
    }

    public function test_superadmin_can_reject_pending_void_request()
    {
        $cashier = User::factory()->create(['role' => 'admin']);
        $cashier->givePermissionTo('fees.manage');

        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $payment = $this->feeService->recordPayment($record, 2000, receivedByUserId: $cashier->id);

        // Submit void request
        $this->feeService->requestPaymentVoid($payment->id, 'Disputed fee', $cashier->id);

        // Superadmin rejects request
        $this->feeService->rejectPendingVoid(
            paymentId: $payment->id,
            rejectionReason: 'Verified with bank, funds confirmed received',
            rejectorUserId: $this->admin->id
        );

        $payment->refresh();
        $this->assertFalse($payment->is_void);
        $this->assertEquals('rejected', $payment->void_status);
        $this->assertEquals('Verified with bank, funds confirmed received', $payment->void_rejection_reason);

        // Balance remains paid
        $record->refresh();
        $this->assertEquals(3000, $record->balance);
    }

    public function test_void_policy_disabled_blocks_all_voids()
    {
        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $payment = $this->feeService->recordPayment($record, 2000);

        // Disable void policy
        $this->feeService->updateVoidPolicy(enabled: false);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Fee payment voiding is currently disabled by institutional policy.');

        $this->feeService->voidPayment(
            paymentId: $payment->id,
            reason: 'Attempted void while disabled',
            authorizerUserId: $this->admin->id
        );
    }

    public function test_livewire_student_ledger_approvals_and_policy_workflow()
    {
        $record = FeeRecord::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-08',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-08-10',
        ]);

        $payment = $this->feeService->recordPayment($record, 2000, receivedByUserId: $this->admin->id);

        // 1. Submit void request as cashier
        $cashier = User::factory()->create(['role' => 'admin', 'password' => bcrypt('password123')]);
        $cashier->givePermissionTo('fees.manage');

        $this->actingAs($cashier);
        Livewire::test(StudentLedger::class, ['studentId' => $this->student->id])
            ->call('openVoidModal', $payment->id)
            ->set('voidMode', 'request')
            ->set('voidReasonType', 'Entered Wrong Amount')
            ->set('voidReasonNotes', 'Mistyped 2000 instead of 1000')
            ->call('confirmVoid');

        $payment->refresh();
        $this->assertEquals('pending', $payment->void_status);

        // 2. Switch to Super Admin and inspect approvals queue
        $this->actingAs($this->admin);
        $component = Livewire::test(StudentLedger::class, ['studentId' => $this->student->id]);
        $component->assertSee('Void Approvals');
        $component->assertSee('PENDING VOID');

        // Open approvals modal
        $component->call('openApprovalsModal')
            ->assertSet('showApprovalsModal', true)
            ->assertSee($payment->receipt_number);

        // Approve request
        $component->call('promptAuthorizeRequest', $payment->id)
            ->set('approvalPassword', 'password') // factory default password is 'password'
            ->call('confirmAuthorizeRequest');

        $payment->refresh();
        $this->assertTrue($payment->is_void);
        $this->assertEquals('approved', $payment->void_status);

        // 3. Test policy configuration
        $component->call('openPolicyModal')
            ->assertSet('showPolicyModal', true)
            ->set('policyVoidEnabled', false)
            ->call('savePolicy');

        $this->assertFalse($this->feeService->isVoidPolicyEnabled());
    }
}

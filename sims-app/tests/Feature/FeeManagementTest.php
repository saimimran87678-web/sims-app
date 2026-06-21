<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\FeeRecord;
use App\Models\FeeRecordItem;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Livewire\Admin\Fee\InvoiceGenerator;
use Livewire\Livewire;

class FeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private $session;
    private $class;
    private $student;
    private $admin;

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

        $this->class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $this->session->id,
        ]);

        $this->student = Student::create([
            'name' => 'Student A',
            'roll_no' => '1',
            'admission_no' => 'ADM-001',
            'class_id' => $this->class->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');
        
        $this->actingAs($this->admin);
    }

    public function test_discount_fields_are_saved_properly_with_description_and_category(): void
    {
        // 1. Test global saving
        Livewire::test(InvoiceGenerator::class)
            ->set('selectedClassId', $this->class->id)
            ->set('billingMonth', '2026-06')
            ->set('baseItems', [
                ['id' => '1', 'name' => 'Tuition Fee', 'amount' => '2000', 'category' => 'monthly']
            ])
            ->set('currentDiscountInput', '200')
            ->set('currentDiscountDescInput', 'Sibling Discount')
            ->set('currentDiscountCategoryInput', 'monthly')
            ->call('generateInvoices');

        $this->assertDatabaseHas('fee_records', [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'period' => '2026-06',
            'total_amount' => 1800.00, // 2000 - 200
        ]);

        $this->assertDatabaseHas('fee_record_items', [
            'fee_head_name' => 'Discount',
            'amount' => -200.00,
            'description' => 'Sibling Discount',
            'category' => 'monthly',
        ]);
    }

    public function test_billing_month_change_carries_over_monthly_items_but_not_one_time_items(): void
    {
        // 1. Save June fees (Tuition Fee as monthly, Admission Fee as one_time, and a monthly discount)
        Livewire::test(InvoiceGenerator::class)
            ->set('selectedClassId', $this->class->id)
            ->set('billingMonth', '2026-06')
            ->set('baseItems', [
                ['id' => '1', 'name' => 'Tuition Fee', 'amount' => '2000', 'category' => 'monthly'],
                ['id' => '2', 'name' => 'Admission Fee', 'amount' => '5000', 'category' => 'one_time'],
            ])
            ->set('currentDiscountInput', '150')
            ->set('currentDiscountDescInput', 'Scholarship')
            ->set('currentDiscountCategoryInput', 'monthly')
            ->call('generateInvoices');

        // Verify database records for June
        $this->assertDatabaseHas('fee_records', [
            'student_id' => $this->student->id,
            'period' => '2026-06',
            'total_amount' => 6850.00, // 2000 + 5000 - 150
        ]);

        // 2. Go to July (which doesn't have any saved vouchers yet)
        // We assert that the monthly tuition fee and monthly discount carry forward, but not the one_time Admission Fee
        $test = Livewire::test(InvoiceGenerator::class)
            ->set('selectedClassId', $this->class->id)
            ->set('billingMonth', '2026-07');

        $baseItems = $test->get('baseItems');
        $this->assertCount(1, $baseItems);
        $this->assertEquals('Tuition Fee', $baseItems[0]['name']);
        $this->assertEquals(2000.00, $baseItems[0]['amount']);
        $this->assertEquals('monthly', $baseItems[0]['category']);

        $test->assertSet('currentDiscountInput', 150.00)
            ->assertSet('currentDiscountDescInput', 'Scholarship')
            ->assertSet('currentDiscountCategoryInput', 'monthly');
    }

    public function test_custom_student_fee_preservation_and_carry_forward()
    {
        // 1. Create a second student in the same class
        $studentB = \App\Models\Student::create([
            'class_id' => $this->class->id,
            'name' => 'Student B',
            'roll_no' => 'ROLL-B01',
            'admission_no' => 'ADM-B01',
        ]);

        // 2. Class-wide save for June with Tuition = 2000
        Livewire::test(InvoiceGenerator::class)
            ->set('selectedClassId', $this->class->id)
            ->set('billingMonth', '2026-06')
            ->set('baseItems', [
                ['id' => '1', 'name' => 'Tuition Fee', 'amount' => '2000', 'category' => 'monthly']
            ])
            ->call('generateInvoices');

        // 3. Customize Student B's Tuition to 2500 and discount to 100 in June
        Livewire::test(InvoiceGenerator::class)
            ->set('selectedClassId', $this->class->id)
            ->set('billingMonth', '2026-06')
            ->set('selectedTarget', $studentB->id)
            ->set('baseItems', [
                ['id' => '1', 'name' => 'Tuition Fee', 'amount' => '2500', 'category' => 'monthly']
            ])
            ->set('currentDiscountInput', '100')
            ->set('currentDiscountDescInput', 'B Discount')
            ->set('currentDiscountCategoryInput', 'monthly')
            ->call('generateInvoices');

        // Verify Student B has a custom record in June
        $recordBJune = \App\Models\FeeRecord::where('student_id', $studentB->id)->where('period', '2026-06')->first();
        $this->assertTrue((bool)$recordBJune->is_custom);
        $this->assertEquals(2400.00, $recordBJune->total_amount); // 2500 - 100

        // 4. In July: Save class-wide with Tuition = 2200 and includeCustom = false
        Livewire::test(InvoiceGenerator::class)
            ->set('selectedClassId', $this->class->id)
            ->set('billingMonth', '2026-07')
            ->set('includeCustom', false)
            ->set('baseItems', [
                ['id' => '1', 'name' => 'Tuition Fee', 'amount' => '2200', 'category' => 'monthly']
            ])
            ->call('generateInvoices');

        // Student A (not custom) should get June's template updated to the new class fee of 2200 (since no June items are specific to them, they get the new class-wide items)
        $recordAJuly = \App\Models\FeeRecord::where('student_id', $this->student->id)->where('period', '2026-07')->first();
        $this->assertEquals(2200.00, $recordAJuly->total_amount);

        // Student B (custom) should have carried forward their custom prior monthly items (Tuition = 2500) and discount (100)
        $recordBJuly = \App\Models\FeeRecord::where('student_id', $studentB->id)->where('period', '2026-07')->first();
        $this->assertTrue((bool)$recordBJuly->is_custom);
        $this->assertEquals(2400.00, $recordBJuly->total_amount); // 2500 - 100

        // 5. In July: Save class-wide with Tuition = 2300 and includeCustom = true
        Livewire::test(InvoiceGenerator::class)
            ->set('selectedClassId', $this->class->id)
            ->set('billingMonth', '2026-07')
            ->set('includeCustom', true)
            ->set('baseItems', [
                ['id' => '1', 'name' => 'Tuition Fee', 'amount' => '2300', 'category' => 'monthly']
            ])
            ->call('generateInvoices');

        // Both Student A and Student B should now have Tuition = 2300 (overwritten)
        $recordAJulyUpdated = \App\Models\FeeRecord::where('student_id', $this->student->id)->where('period', '2026-07')->first();
        $this->assertEquals(2300.00, $recordAJulyUpdated->total_amount);

        $recordBJulyUpdated = \App\Models\FeeRecord::where('student_id', $studentB->id)->where('period', '2026-07')->first();
        $this->assertEquals(2200.00, $recordBJulyUpdated->total_amount); // 2300 - 100 custom discount
    }
}

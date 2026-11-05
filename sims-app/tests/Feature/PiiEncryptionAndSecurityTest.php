<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\FeeRecord;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class PiiEncryptionAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $session;
    protected $class;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('Super Admin');

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'shift_type' => 'Morning',
        ]);

        $this->class = Classes::create([
            'name' => 'Grade 10',
            'numeric_value' => 10,
            'academic_session_id' => $this->session->id,
            'shift_type' => 'morning',
            'order' => 1,
        ]);
    }

    public function test_student_pii_fields_are_encrypted_at_rest_and_decrypted_on_model()
    {
        $plainFatherCnic = '35202-9876543-1';
        $plainGuardianCnic = '35202-1112223-4';
        $plainBForm = '35202-0001112-3';
        $plainPhone = '03001234567';
        $plainAddress = 'House 123, Street 4, Sector F-8, Islamabad';

        $student = Student::create([
            'name' => 'Zain Ahmed',
            'admission_no' => 'ADM-9001',
            'b_form_number' => $plainBForm,
            'father_name' => 'Ahmed Khan',
            'father_cnic' => $plainFatherCnic,
            'guardian_cnic' => $plainGuardianCnic,
            'guardian_relation' => 'Father',
            'phone' => $plainPhone,
            'address' => $plainAddress,
            'status' => 'active',
            'admission_date' => '2026-04-01',
        ]);

        // 1. Raw DB Inspection: Confirm data is stored encrypted at rest
        $rawRow = DB::table('students')->where('id', $student->id)->first();

        $this->assertNotEquals($plainFatherCnic, $rawRow->father_cnic);
        $this->assertNotEquals($plainGuardianCnic, $rawRow->guardian_cnic);
        $this->assertNotEquals($plainBForm, $rawRow->b_form_number);
        $this->assertNotEquals($plainPhone, $rawRow->phone);
        $this->assertNotEquals($plainAddress, $rawRow->address);

        // Verify that raw database values are valid encrypted payloads
        $this->assertEquals($plainFatherCnic, Crypt::decryptString($rawRow->father_cnic));
        $this->assertEquals($plainBForm, Crypt::decryptString($rawRow->b_form_number));
        $this->assertEquals($plainPhone, Crypt::decryptString($rawRow->phone));
        $this->assertEquals($plainAddress, Crypt::decryptString($rawRow->address));

        // 2. Blind Index Hashes are generated accurately
        $expectedFatherHash = Student::hashBlindIndex($plainFatherCnic);
        $expectedBFormHash = Student::hashBlindIndex($plainBForm);
        $expectedPhoneHash = Student::hashBlindIndex($plainPhone);

        $this->assertEquals($expectedFatherHash, $rawRow->father_cnic_hash);
        $this->assertEquals($expectedBFormHash, $rawRow->b_form_hash);
        $this->assertEquals($expectedPhoneHash, $rawRow->phone_hash);

        // 3. Eloquent Model transparently decrypts attributes
        $fresh = Student::find($student->id);
        $this->assertEquals($plainFatherCnic, $fresh->father_cnic);
        $this->assertEquals($plainGuardianCnic, $fresh->guardian_cnic);
        $this->assertEquals($plainBForm, $fresh->b_form_number);
        $this->assertEquals($plainPhone, $fresh->phone);
        $this->assertEquals($plainAddress, $fresh->address);
    }

    public function test_blind_index_hashes_enable_exact_lookups_and_sibling_grouping()
    {
        $sharedFatherCnic = '37405-5556667-1';

        $student1 = Student::create([
            'name' => 'Bilal Khan',
            'admission_no' => 'ADM-8001',
            'father_name' => 'Tariq Khan',
            'father_cnic' => $sharedFatherCnic,
            'status' => 'active',
            'admission_date' => '2026-04-01',
        ]);

        $student2 = Student::create([
            'name' => 'Sara Khan',
            'admission_no' => 'ADM-8002',
            'father_name' => 'Tariq Khan',
            'father_cnic' => $sharedFatherCnic,
            'status' => 'active',
            'admission_date' => '2026-04-05',
        ]);

        // Sibling relationship works via blind index hash
        $this->assertTrue($student1->siblings->contains('id', $student2->id));
        $this->assertTrue($student2->siblings->contains('id', $student1->id));

        // Family unit returns both chronologically
        $family1 = $student1->family_unit;
        $this->assertCount(2, $family1);
        $this->assertEquals($student1->id, $family1[0]->id);
        $this->assertEquals($student2->id, $family1[1]->id);

        $this->assertEquals(0, $student1->sibling_rank);
        $this->assertEquals(1, $student2->sibling_rank);
    }

    public function test_portal_login_authenticates_with_encrypted_father_cnic()
    {
        $fatherCnic = '35201-1234567-9';
        $student = Student::create([
            'name' => 'Usman Tariq',
            'admission_no' => 'ADM-7001',
            'father_name' => 'Tariq Mehmood',
            'father_cnic' => $fatherCnic,
            'status' => 'active',
            'admission_date' => '2026-04-01',
        ]);

        Livewire::test(\App\Livewire\Student\Auth\PortalLogin::class)
            ->set('admission_no', 'ADM-7001')
            ->set('father_cnic', $fatherCnic)
            ->call('login')
            ->assertRedirect(route('student.dashboard'));

        $this->assertEquals($student->id, session('student_portal_id'));
    }

    public function test_student_photo_route_blocks_unauthorized_guests()
    {
        $student = Student::create([
            'name' => 'Ayesha Noor',
            'admission_no' => 'ADM-6001',
            'father_name' => 'Noor Muhammad',
            'status' => 'active',
            'admission_date' => '2026-04-01',
        ]);

        // Unauthenticated guest request without signature
        $response = $this->get(route('student.photo', $student->id));
        $response->assertStatus(403);
    }

    public function test_student_photo_route_allows_authorized_users_and_portal_session()
    {
        $student = Student::create([
            'name' => 'Ayesha Noor',
            'admission_no' => 'ADM-6002',
            'father_name' => 'Noor Muhammad',
            'status' => 'active',
            'admission_date' => '2026-04-01',
        ]);

        // 1. Authorized Admin
        $this->actingAs($this->admin);
        $adminResp = $this->get(route('student.photo', $student->id));
        $adminResp->assertStatus(200);

        // 2. Portal Session User
        auth()->logout();
        session(['student_portal_id' => $student->id]);
        $portalResp = $this->get(route('student.photo', $student->id));
        $portalResp->assertStatus(200);

        // 3. Signed Temporary URL (for RFID attendance kiosks)
        session()->forget('student_portal_id');
        $signedUrl = URL::temporarySignedRoute('student.photo', now()->addMinutes(30), ['student' => $student->id]);
        $signedResp = $this->get($signedUrl);
        $signedResp->assertStatus(200);
    }

    public function test_fee_voucher_expires_after_configured_period()
    {
        $student = Student::create([
            'name' => 'Hamza Ali',
            'admission_no' => 'ADM-5001',
            'status' => 'active',
            'admission_date' => '2026-04-01',
        ]);

        $record = FeeRecord::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'period' => '2026-05',
            'cycle' => 'monthly',
            'total_amount' => 5000,
            'paid_amount' => 0,
            'balance' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-05-15',
        ]);

        $this->assertNotNull($record->access_token);
        $this->assertNotNull($record->access_token_expires_at);
        $this->assertFalse($record->isAccessTokenExpired());

        // 1. Valid active token opens voucher page
        $responseActive = $this->get(route('public.voucher.show', $record->access_token));
        $responseActive->assertStatus(200);

        // 2. Expire the token by setting access_token_expires_at in the past
        $record->update(['access_token_expires_at' => now()->subDay()]);
        $this->assertTrue($record->isAccessTokenExpired());

        $responseExpired = $this->get(route('public.voucher.show', $record->access_token));
        $responseExpired->assertStatus(410);
        $responseExpired->assertSee('Voucher Link Expired');

        $responsePdfExpired = $this->get(route('public.voucher.pdf', $record->access_token));
        $responsePdfExpired->assertStatus(410);

        // 3. Regenerate token restores access with fresh token
        $oldToken = $record->access_token;
        $newToken = $record->regenerateAccessToken(daysValid: 15);

        $this->assertNotEquals($oldToken, $newToken);
        $this->assertFalse($record->fresh()->isAccessTokenExpired());

        $responseRenewed = $this->get(route('public.voucher.show', $newToken));
        $responseRenewed->assertStatus(200);
    }

    public function test_whatsapp_queue_purge_removes_old_messages()
    {
        // 1. Aged sent message (40 days old) -> Should be purged
        DB::table('whatsapp_queue')->insert([
            'phone' => '03001234567',
            'message' => 'Old fee receipt message with sensitive details',
            'status' => 'sent',
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        // 2. Aged failed message (40 days old) -> Should be purged
        DB::table('whatsapp_queue')->insert([
            'phone' => '03001234568',
            'message' => 'Old failed message',
            'status' => 'failed',
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        // 3. Aged pending message (40 days old) -> Should NOT be purged (still pending dispatch)
        DB::table('whatsapp_queue')->insert([
            'phone' => '03001234569',
            'message' => 'Pending message',
            'status' => 'pending',
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        // 4. Recent sent message (5 days old) -> Should NOT be purged
        DB::table('whatsapp_queue')->insert([
            'phone' => '03001234570',
            'message' => 'Recent sent message',
            'status' => 'sent',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $this->assertEquals(4, DB::table('whatsapp_queue')->count());

        Artisan::call('whatsapp:purge-queue', ['--days' => 30]);

        $this->assertEquals(2, DB::table('whatsapp_queue')->count());
        $this->assertDatabaseMissing('whatsapp_queue', ['phone' => '03001234567']);
        $this->assertDatabaseMissing('whatsapp_queue', ['phone' => '03001234568']);
        $this->assertDatabaseHas('whatsapp_queue', ['phone' => '03001234569']);
        $this->assertDatabaseHas('whatsapp_queue', ['phone' => '03001234570']);
    }

    public function test_activity_logs_mask_sensitive_pii()
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'name' => 'Daniyal Raza',
            'admission_no' => 'ADM-4001',
            'father_name' => 'Raza Ali',
            'father_cnic' => '35202-7778889-1',
            'phone' => '03331112233',
            'b_form_number' => '35202-9990001-2',
            'status' => 'active',
            'admission_date' => '2026-04-01',
        ]);

        $log = ActivityLog::where('model_id', (string) $student->id)
            ->where('model_type', Student::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('••••••••', $log->new_values['father_cnic'] ?? null);
        $this->assertEquals('••••••••', $log->new_values['phone'] ?? null);
        $this->assertEquals('••••••••', $log->new_values['b_form_number'] ?? null);
    }
}

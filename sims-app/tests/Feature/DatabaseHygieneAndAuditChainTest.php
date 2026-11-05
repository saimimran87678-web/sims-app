<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Models\AcademicSession;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use DomainException;

class DatabaseHygieneAndAuditChainTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $session1;
    protected $session2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session1 = AcademicSession::create([
            'name' => 'Session 2026-A',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $this->session2 = AcademicSession::create([
            'name' => 'Session 2027-B',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'shift_type' => 'Regular',
            'is_active' => false,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Audit Admin',
            'email' => 'audit.admin@sims.test',
            'role' => 'admin',
        ]);
    }

    public function test_redundant_indexes_are_pruned()
    {
        $indexNames = collect(DB::select("SELECT name FROM sqlite_master WHERE type='index'"))
            ->pluck('name')
            ->all();

        // 1. Redundant students_father_cnic_index must not exist
        $this->assertNotContains('students_father_cnic_index', $indexNames);
        // But composite index covering father_cnic + status must exist
        $this->assertContains('idx_students_father_cnic_status', $indexNames);

        // 2. Duplicate idx_fr_session_status must not exist
        $this->assertNotContains('idx_fr_session_status', $indexNames);
        // Canonical idx_fee_records_session_status must exist
        $this->assertContains('idx_fee_records_session_status', $indexNames);
    }

    public function test_settings_null_safe_unique_constraint_blocks_duplicate_global_keys()
    {
        // 1. First global setting succeeds
        DB::table('settings')->insert([
            'key' => 'institution_motto',
            'value' => 'Knowledge is Power',
            'academic_session_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Second global setting with identical key MUST fail under SQLite partial unique index
        $this->expectException(QueryException::class);

        DB::table('settings')->insert([
            'key' => 'institution_motto',
            'value' => 'Different Motto',
            'academic_session_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_settings_allow_same_key_across_different_academic_sessions()
    {
        // Setting for Session 1
        $s1 = DB::table('settings')->insert([
            'key' => 'exam_term',
            'value' => 'Midterm 2026',
            'academic_session_id' => $this->session1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setting for Session 2 with SAME key succeeds
        $s2 = DB::table('settings')->insert([
            'key' => 'exam_term',
            'value' => 'Midterm 2027',
            'academic_session_id' => $this->session2->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($s1);
        $this->assertTrue($s2);

        // Duplicate for same session fails
        $this->expectException(QueryException::class);
        DB::table('settings')->insert([
            'key' => 'exam_term',
            'value' => 'Duplicate Midterm',
            'academic_session_id' => $this->session1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_teacher_attendances_null_safe_unique_constraint()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $date = '2026-03-15';

        // 1. Global teacher attendance
        DB::table('teacher_attendances')->insert([
            'teacher_id' => $teacher->id,
            'date' => $date,
            'shift_type' => 'morning',
            'academic_session_id' => null,
            'status' => 'Present',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Duplicate global attendance fails
        try {
            DB::table('teacher_attendances')->insert([
                'teacher_id' => $teacher->id,
                'date' => $date,
                'shift_type' => 'morning',
                'academic_session_id' => null,
                'status' => 'Late',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Expected QueryException for duplicate global teacher attendance');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }

        // Attendance for evening shift on same date succeeds
        $evening = DB::table('teacher_attendances')->insert([
            'teacher_id' => $teacher->id,
            'date' => $date,
            'shift_type' => 'evening',
            'academic_session_id' => null,
            'status' => 'Present',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertTrue($evening);
    }

    public function test_cryptographic_audit_logging_computes_valid_hash_chain()
    {
        $genesisLog = ActivityLog::orderBy('id', 'asc')->first();
        if ($genesisLog) {
            $this->assertEquals(str_repeat('0', 64), $genesisLog->previous_hash);
            $this->assertEquals(64, strlen($genesisLog->record_hash));
        }

        $log1 = ActivityLogger::log(
            action: 'created',
            description: 'Created first test entity',
            user: $this->user,
            module: 'testing'
        );

        $log2 = ActivityLogger::log(
            action: 'updated',
            description: 'Updated test entity',
            user: $this->user,
            module: 'testing'
        );

        $log3 = ActivityLogger::log(
            action: 'deleted',
            description: 'Deleted test entity',
            user: $this->user,
            module: 'testing'
        );

        $this->assertNotNull($log1);
        $this->assertNotNull($log2);
        $this->assertNotNull($log3);

        // Second log links to first log's record_hash
        $this->assertEquals($log1->record_hash, $log2->previous_hash);

        // Third log links to second log's record_hash
        $this->assertEquals($log2->record_hash, $log3->previous_hash);

        // Verify CLI command returns 0 (healthy chain)
        $exitCode = $this->artisan('audit:verify-chain')->run();
        $this->assertEquals(0, $exitCode);
    }

    public function test_audit_verify_detects_data_tampering()
    {
        // Create 3 valid logs
        ActivityLogger::log('created', 'Valid log 1', user: $this->user, module: 'test');
        $log2 = ActivityLogger::log('updated', 'Valid log 2', user: $this->user, module: 'test');
        ActivityLogger::log('deleted', 'Valid log 3', user: $this->user, module: 'test');

        // Tamper with log2 directly via raw SQL
        DB::table('activity_logs')
            ->where('id', $log2->id)
            ->update(['description' => 'Hacked description: unauthorized tampering']);

        // audit:verify-chain must detect signature mismatch and return failure exit code 1
        $exitCode = $this->artisan('audit:verify-chain')->run();
        $this->assertEquals(1, $exitCode);
    }

    public function test_audit_verify_detects_deleted_log_in_chain()
    {
        // Create 3 valid logs
        ActivityLogger::log('created', 'Chain log 1', user: $this->user, module: 'test');
        $log2 = ActivityLogger::log('updated', 'Chain log 2 to be deleted', user: $this->user, module: 'test');
        ActivityLogger::log('deleted', 'Chain log 3', user: $this->user, module: 'test');

        // Covertly delete log 2 from SQLite
        DB::table('activity_logs')->where('id', $log2->id)->delete();

        // audit:verify-chain must detect broken link between log 1 and log 3
        $exitCode = $this->artisan('audit:verify-chain')->run();
        $this->assertEquals(1, $exitCode);
    }

    public function test_activity_log_model_blocks_updating()
    {
        $log = ActivityLogger::log('created', 'Immutable log entry', user: $this->user, module: 'test');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Activity logs are cryptographically sealed and immutable.');

        $log->update(['description' => 'Attempted in-app edit']);
    }
}

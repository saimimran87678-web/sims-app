<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\LicenseStatus;
use App\Services\LicenseVerifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class LicenseSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure dummy keys for the test
        config([
            'services.license.integrity_key' => 'test_integrity_key_123',
            'services.license.vendor_phone' => '1234567890',
            'services.license.school_id' => 'school_a',
        ]);
    }

    /** @test */
    public function it_identifies_unlicensed_state_when_database_is_empty()
    {
        // Make sure no records exist
        DB::table('software_licenses')->truncate();

        $status = LicenseStatus::computeStatus();

        $this->assertEquals(LicenseStatus::STAGE_BLOCKED, $status['stage']);
        $this->assertEquals('unlicensed', $status['reason']);
    }

    /** @test */
    public function it_detects_database_integrity_tampering()
    {
        $licenseKey = 'test-lic-key';
        $expiresAt = Carbon::now()->addDays(30)->toDateTimeString();
        $statusStr = 'active';

        // Compute valid hash
        $validHash = LicenseVerifier::computeIntegrityHash($licenseKey, $expiresAt, $statusStr, 'school_a');

        // Insert tempered hash manually
        DB::table('software_licenses')->insert([
            'license_key' => encrypt($licenseKey),
            'school_id' => 'school_a',
            'firebase_refresh_token' => encrypt('dummy_refresh'),
            'status' => encrypt($statusStr),
            'plan' => encrypt('premium'),
            'expires_at' => $expiresAt,
            'rsa_signature' => 'dummy_signature',
            'integrity_hash' => 'tempered_hash_value', // Invalid hash!
            'last_online_verified_at' => Carbon::now(),
        ]);

        $status = LicenseStatus::computeStatus();

        $this->assertEquals(LicenseStatus::STAGE_BLOCKED, $status['stage']);
        $this->assertEquals('tampered_hash', $status['reason']);
    }

    /** @test */
    public function it_gathers_timeline_stages_correctly()
    {
        // We will mock RSA and Integrity checks by inserting matching values
        $licenseKey = 'valid-key';
        $statusStr = 'active';
        $schoolId = 'school_a';

        // Prepare helper to update records
        $insertLicenseWithExpiry = function ($expiresAt, $lastVerified = null) use ($licenseKey, $statusStr, $schoolId) {
            DB::table('software_licenses')->truncate();
            
            $expiresAtStr = $expiresAt ? $expiresAt->toDateTimeString() : null;
            $hash = LicenseVerifier::computeIntegrityHash($licenseKey, $expiresAtStr, $statusStr, $schoolId);

            // Generate a valid mock RSA signature for tests since we verify RSA in tests
            // In a real environment, we'd sign with private key and verify with public key.
            // For testing, let's mock/disable RSA check or use key generation.
            // Let's create an actual RSA signature that verifies against a temporary keypair.
            $keys = openssl_pkey_new([
                "private_key_bits" => 1024,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ]);
            openssl_pkey_export($keys, $privateKey);
            $details = openssl_pkey_get_details($keys);
            $publicKey = $details['key'];

            // Set config keys dynamically
            config(['services.license.rsa_public_key' => $publicKey]);

            $normalizedExpires = LicenseVerifier::normalizeExpiresAt($licenseKey, $expiresAtStr);
            $payload = $licenseKey . '|' . $normalizedExpires . '|' . $statusStr;
            openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $base64Signature = base64_encode($signature);

            DB::table('software_licenses')->insert([
                'license_key' => encrypt($licenseKey),
                'school_id' => $schoolId,
                'firebase_refresh_token' => encrypt('token'),
                'status' => encrypt($statusStr),
                'plan' => encrypt('premium'),
                'expires_at' => $expiresAtStr,
                'rsa_signature' => $base64Signature,
                'integrity_hash' => $hash,
                'last_online_verified_at' => $lastVerified ?? Carbon::now(),
            ]);
        };

        // Case A: Expiry > 3 days (ACTIVE)
        $insertLicenseWithExpiry(Carbon::now()->addDays(5));
        $status = LicenseStatus::computeStatus();
        $this->assertEquals(LicenseStatus::STAGE_ACTIVE, $status['stage']);

        // Case B: Expiry <= 3 days (WARNING)
        $insertLicenseWithExpiry(Carbon::now()->addDays(2));
        $status = LicenseStatus::computeStatus();
        $this->assertEquals(LicenseStatus::STAGE_WARNING, $status['stage']);

        // Case C: Expired by <= 3 days (GRACE)
        $insertLicenseWithExpiry(Carbon::now()->subDays(1));
        $status = LicenseStatus::computeStatus();
        $this->assertEquals(LicenseStatus::STAGE_GRACE, $status['stage']);

        // Case D: Expired by 4 to 10 days (LOCKED)
        $insertLicenseWithExpiry(Carbon::now()->subDays(5));
        $status = LicenseStatus::computeStatus();
        $this->assertEquals(LicenseStatus::STAGE_LOCKED, $status['stage']);

        // Case E: Expired by > 10 days (BLOCKED)
        $insertLicenseWithExpiry(Carbon::now()->subDays(12));
        $status = LicenseStatus::computeStatus();
        $this->assertEquals(LicenseStatus::STAGE_BLOCKED, $status['stage']);
    }

    /** @test */
    public function it_blocks_writes_in_locked_stage()
    {
        // Force status to LOCKED in session
        session(['sims_license_status' => [
            'stage' => LicenseStatus::STAGE_LOCKED,
            'reason' => 'expired_locked',
            'message' => 'System is in read-only mode.',
        ]]);

        $this->assertFalse(LicenseStatus::canWrite());
        $this->assertFalse(\canWrite()); // global helper
    }

    /** @test */
    public function it_allows_writes_in_active_stage()
    {
        // Force status to ACTIVE in session
        session(['sims_license_status' => [
            'stage' => LicenseStatus::STAGE_ACTIVE,
            'reason' => 'active',
        ]]);

        $this->assertTrue(LicenseStatus::canWrite());
        $this->assertTrue(\canWrite()); // global helper
    }
}

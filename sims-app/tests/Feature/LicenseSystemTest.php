<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\LicenseStatus;
use App\Services\LicenseVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class LicenseSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure dummy keys for tests
        config([
            'services.license.integrity_key' => 'test_integrity_key_123',
            'services.license.vendor_phone'   => '1234567890',
            'services.license.school_id'      => 'school_a',
        ]);

        // Always start with a clean cache so tests don't bleed into each other
        Cache::forget(LicenseStatus::CACHE_KEY);
    }

    protected function tearDown(): void
    {
        Cache::forget(LicenseStatus::CACHE_KEY);
        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate a fresh RSA keypair, set the public key in config, and return
     * a valid base64 signature for the given license parameters.
     */
    private function generateSignedRecord(
        string  $licenseKey,
        ?string $expiresAtStr,
        string  $statusStr
    ): array {
        $keys = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($keys, $privateKey);
        $details   = openssl_pkey_get_details($keys);
        $publicKey = $details['key'];

        config(['services.license.rsa_public_key' => $publicKey]);

        // Use LicenseVerifier::buildPayload so the test payload always matches the verifier
        $payload = LicenseVerifier::buildPayload($licenseKey, $expiresAtStr, $statusStr);
        openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return [
            'signature' => base64_encode($signature),
            'hash'      => LicenseVerifier::computeIntegrityHash($licenseKey, $expiresAtStr, $statusStr, 'school_a'),
        ];
    }

    private function insertLicense(
        string  $licenseKey,
        ?Carbon $expiresAt,
        string  $statusStr,
        ?Carbon $lastVerified = null
    ): void {
        DB::table('software_licenses')->truncate();

        $expiresAtStr = $expiresAt?->utc()->format('Y-m-d H:i:s');
        ['signature' => $sig, 'hash' => $hash] = $this->generateSignedRecord($licenseKey, $expiresAtStr, $statusStr);

        DB::table('software_licenses')->insert([
            'license_key'             => encrypt($licenseKey),
            'school_id'               => 'school_a',
            'firebase_refresh_token'  => encrypt('dummy_token'),
            'status'                  => encrypt($statusStr),
            'plan'                    => encrypt('premium'),
            'expires_at'              => $expiresAtStr,
            'rsa_signature'           => $sig,
            'integrity_hash'          => $hash,
            'last_online_verified_at' => $lastVerified ?? Carbon::now(),
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /** @test */
    public function it_identifies_unlicensed_state_when_database_is_empty()
    {
        DB::table('software_licenses')->truncate();

        $status = LicenseStatus::computeStatus();

        $this->assertEquals(LicenseStatus::STAGE_BLOCKED, $status['stage']);
        $this->assertEquals('unlicensed', $status['reason']);
    }

    /** @test */
    public function it_detects_database_integrity_tampering()
    {
        $licenseKey = 'test-lic-key';
        $expiresAt  = Carbon::now()->addDays(30)->utc()->format('Y-m-d H:i:s');
        $statusStr  = 'active';

        ['signature' => $sig] = $this->generateSignedRecord($licenseKey, $expiresAt, $statusStr);

        DB::table('software_licenses')->insert([
            'license_key'             => encrypt($licenseKey),
            'school_id'               => 'school_a',
            'firebase_refresh_token'  => encrypt('dummy_refresh'),
            'status'                  => encrypt($statusStr),
            'plan'                    => encrypt('premium'),
            'expires_at'              => $expiresAt,
            'rsa_signature'           => $sig,
            'integrity_hash'          => 'tampered_hash_that_will_never_match',
            'last_online_verified_at' => Carbon::now(),
        ]);

        $status = LicenseStatus::computeStatus();

        $this->assertEquals(LicenseStatus::STAGE_BLOCKED, $status['stage']);
        $this->assertEquals('tampered_hash', $status['reason']);
    }

    /** @test */
    public function it_gathers_timeline_stages_correctly()
    {
        $licenseKey = 'valid-key';
        $statusStr  = 'active';

        // Case A: Expiry > 3 days → ACTIVE
        $this->insertLicense($licenseKey, Carbon::now()->addDays(5), $statusStr);
        $this->assertEquals(LicenseStatus::STAGE_ACTIVE, LicenseStatus::computeStatus()['stage']);

        // Case B: Expiry ≤ 3 days → WARNING
        $this->insertLicense($licenseKey, Carbon::now()->addDays(2), $statusStr);
        $this->assertEquals(LicenseStatus::STAGE_WARNING, LicenseStatus::computeStatus()['stage']);

        // Case C: Expired by ≤ 3 days → GRACE
        $this->insertLicense($licenseKey, Carbon::now()->subDays(1), $statusStr);
        $this->assertEquals(LicenseStatus::STAGE_GRACE, LicenseStatus::computeStatus()['stage']);

        // Case D: Expired by 4–10 days → LOCKED
        $this->insertLicense($licenseKey, Carbon::now()->subDays(5), $statusStr);
        $this->assertEquals(LicenseStatus::STAGE_LOCKED, LicenseStatus::computeStatus()['stage']);

        // Case E: Expired by > 10 days → BLOCKED
        $this->insertLicense($licenseKey, Carbon::now()->subDays(12), $statusStr);
        $this->assertEquals(LicenseStatus::STAGE_BLOCKED, LicenseStatus::computeStatus()['stage']);
    }

    /** @test */
    public function it_blocks_writes_in_locked_stage()
    {
        Cache::put(LicenseStatus::CACHE_KEY, [
            'stage'   => LicenseStatus::STAGE_LOCKED,
            'reason'  => 'expired_locked',
            'message' => 'System is in read-only mode.',
        ], LicenseStatus::CACHE_TTL);

        $this->assertFalse(LicenseStatus::canWrite());
        $this->assertFalse(\canWrite());
    }

    /** @test */
    public function it_allows_writes_in_active_stage()
    {
        Cache::put(LicenseStatus::CACHE_KEY, [
            'stage'  => LicenseStatus::STAGE_ACTIVE,
            'reason' => 'active',
        ], LicenseStatus::CACHE_TTL);

        $this->assertTrue(LicenseStatus::canWrite());
        $this->assertTrue(\canWrite());
    }
}

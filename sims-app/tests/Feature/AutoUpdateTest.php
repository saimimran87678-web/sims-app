<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AutoUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.version' => '2.5.0']);
        $this->tempDir = storage_path('framework/testing/update_test_' . uniqid());
        File::ensureDirectoryExists($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Create a dummy update zip file containing sample files.
     */
    protected function createDummyZip(string $zipPath, array $files = []): string
    {
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        if (empty($files)) {
            $files = ['version_patch.txt' => 'Patch content'];
        }

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
        return $zipPath;
    }

    public function test_verify_checksum_option_displays_status(): void
    {
        Setting::setGlobal('last_update_checksum', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');
        Setting::setGlobal('last_updated_at', '2026-09-21 14:00:00');

        $this->artisan('sims:update --verify-checksum')
            ->expectsOutputToContain('SIMS Integrity & Checksum Status')
            ->expectsOutputToContain('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855')
            ->assertExitCode(0);
    }

    public function test_update_check_detects_available_update_and_shows_checksum(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $manifestData = [
            'version'         => '2.6.0',
            'download_url'    => 'https://example.com/sims-v2.6.0.zip',
            'checksum'        => 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
            'min_php_version' => '8.2.0',
            'changelog'       => 'Security patches and timetable improvements',
        ];
        File::put($manifestPath, json_encode($manifestData));

        $this->artisan("sims:update --check --manifest={$manifestPath}")
            ->expectsOutputToContain('SIMS Update Check')
            ->expectsOutputToContain('Available Version : v2.6.0')
            ->expectsOutputToContain('abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890')
            ->expectsOutputToContain('Security patches and timetable improvements')
            ->assertExitCode(0);
    }

    public function test_same_version_hotfix_detected_when_checksum_differs(): void
    {
        Setting::setGlobal('last_update_checksum', 'oldchecksum11111111111111111111111111111111111111111111111111111111');

        $manifestPath = $this->tempDir . '/manifest.json';
        $manifestData = [
            'version'         => '2.5.0', // Same as current config version
            'download_url'    => 'https://example.com/sims-v2.5.0-patch.zip',
            'checksum'        => 'newchecksum22222222222222222222222222222222222222222222222222222222',
            'min_php_version' => '8.2.0',
            'changelog'       => 'Critical hotfix patch for v2.5.0',
        ];
        File::put($manifestPath, json_encode($manifestData));

        $this->artisan("sims:update --check --manifest={$manifestPath}")
            ->expectsOutputToContain('SIMS Update Check')
            ->expectsOutputToContain('A hotfix patch for v2.5.0 is available to install (checksum updated).')
            ->assertExitCode(0);
    }

    public function test_same_version_hotfix_detected_when_installed_checksum_is_empty(): void
    {
        Setting::setGlobal('last_update_checksum', '');

        $manifestPath = $this->tempDir . '/manifest.json';
        $manifestData = [
            'version'         => '2.5.0', // Same as current config version
            'download_url'    => 'https://example.com/sims-v2.5.0-patch.zip',
            'checksum'        => 'newchecksum33333333333333333333333333333333333333333333333333333333',
            'min_php_version' => '8.2.0',
            'changelog'       => 'Initial hotfix patch for v2.5.0',
        ];
        File::put($manifestPath, json_encode($manifestData));

        $this->artisan("sims:update --check --manifest={$manifestPath}")
            ->expectsOutputToContain('SIMS Update Check')
            ->expectsOutputToContain('A hotfix patch for v2.5.0 is available to install (checksum updated).')
            ->assertExitCode(0);
    }

    public function test_update_aborts_when_sha256_checksum_mismatches(): void
    {
        $zipPath = $this->tempDir . '/sims-v2.6.0.zip';
        $this->createDummyZip($zipPath);

        // Intentionally provide incorrect checksum
        $manifestPath = $this->tempDir . '/manifest.json';
        $manifestData = [
            'version'         => '2.6.0',
            'download_url'    => $zipPath,
            'checksum'        => '0000000000000000000000000000000000000000000000000000000000000000',
            'min_php_version' => '8.2.0',
        ];
        File::put($manifestPath, json_encode($manifestData));

        $this->artisan("sims:update --manifest={$manifestPath} --force")
            ->expectsOutputToContain('Checksum verification mismatch')
            ->assertExitCode(1);

        // Verify setting was NOT updated
        $this->assertNotEquals('2.6.0', Setting::getGlobal('installed_version'));
    }

    public function test_update_succeeds_when_checksum_matches(): void
    {
        $testFile = 'sims_update_test_' . uniqid() . '.txt';
        $zipPath = $this->tempDir . '/sims-v2.6.0.zip';
        $this->createDummyZip($zipPath, [$testFile => 'verified update content']);

        $realChecksum = hash_file('sha256', $zipPath);

        $manifestPath = $this->tempDir . '/manifest.json';
        $manifestData = [
            'version'         => '2.6.0',
            'download_url'    => $zipPath,
            'checksum'        => $realChecksum,
            'min_php_version' => '8.2.0',
        ];
        File::put($manifestPath, json_encode($manifestData));

        $extractTarget = $this->tempDir . '/extracted';
        File::ensureDirectoryExists($extractTarget);

        $this->artisan("sims:update --manifest={$manifestPath} --extract-to={$extractTarget} --skip-health-check --force")
            ->expectsOutputToContain("SHA-256 Checksum verified: {$realChecksum}")
            ->expectsOutputToContain("SIMS successfully updated to v2.6.0!")
            ->assertExitCode(0);

        // Verify settings were updated
        $this->assertEquals('2.6.0', Setting::getGlobal('installed_version'));
        $this->assertEquals($realChecksum, Setting::getGlobal('last_update_checksum'));
        $this->assertNotNull(Setting::getGlobal('last_updated_at'));

        // Verify file was extracted to specified target
        $this->assertFileExists("{$extractTarget}/{$testFile}");
    }

    public function test_rollback_restores_database_on_failure(): void
    {
        $corruptZipPath = $this->tempDir . '/corrupt.zip';
        File::put($corruptZipPath, 'not a valid zip file');
        $corruptHash = hash_file('sha256', $corruptZipPath);

        $manifestPath = $this->tempDir . '/manifest.json';
        $manifestData = [
            'version'         => '2.7.0',
            'download_url'    => $corruptZipPath,
            'checksum'        => $corruptHash,
            'min_php_version' => '8.2.0',
        ];
        File::put($manifestPath, json_encode($manifestData));

        // Mark a canary setting in database before running update
        Setting::setGlobal('rollback_canary', 'canary_alive');

        $this->artisan("sims:update --manifest={$manifestPath} --skip-health-check --force")
            ->expectsOutputToContain('Corrupt update archive')
            ->expectsOutputToContain('Safe rollback completed')
            ->assertExitCode(1);

        // Verify database is still intact after rollback
        $this->assertEquals('canary_alive', Setting::getGlobal('rollback_canary'));
    }

    public function test_local_package_update_succeeds_directly(): void
    {
        $testFile = 'sims_local_patch_' . uniqid() . '.txt';
        $zipPath = $this->tempDir . '/sims-patch-v2.6.5.zip';
        $this->createDummyZip($zipPath, [
            $testFile => 'local patch content',
            'manifest.json' => json_encode(['version' => '2.6.5', 'min_php_version' => '8.2.0'])
        ]);

        $realChecksum = hash_file('sha256', $zipPath);
        $extractTarget = $this->tempDir . '/extracted_local';
        File::ensureDirectoryExists($extractTarget);

        $this->artisan("sims:update --package={$zipPath} --extract-to={$extractTarget} --skip-health-check --force")
            ->expectsOutputToContain("Inspecting local patch archive")
            ->expectsOutputToContain("Package verified: target v2.6.5")
            ->expectsOutputToContain("SIMS successfully updated to v2.6.5!")
            ->assertExitCode(0);

        $this->assertEquals('2.6.5', Setting::getGlobal('installed_version'));
        $this->assertEquals($realChecksum, Setting::getGlobal('last_update_checksum'));
        $this->assertFileExists("{$extractTarget}/{$testFile}");
    }
}

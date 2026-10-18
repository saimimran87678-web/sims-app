<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\PeriodConfig;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetablePrintHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::clearRuntimeCache();
        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());
        Setting::setGlobal('institute_name', 'Springfield Academy');
        Setting::setGlobal('institute_formal_name', 'Springfield Academy');

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'shift_type' => 'Regular',
        ]);

        // Create 15 classes so that chunking (9 per page) creates at least 2 pages
        for ($i = 1; $i <= 15; $i++) {
            Classes::create([
                'name' => "Class {$i}",
                'numeric_value' => $i,
                'status' => 'active',
                'academic_session_id' => $this->session->id,
            ]);
        }

        // Clear existing period configs
        \Illuminate\Support\Facades\DB::table('period_configs')->delete();

        // Create periods
        PeriodConfig::create([
            'label' => 'Assembly',
            'period_no' => 0,
            'start_time' => '08:00',
            'end_time' => '08:15',
            'is_break' => false,
            'shift_type' => 'regular',
        ]);
        for ($p = 1; $p <= 6; $p++) {
            PeriodConfig::create([
                'label' => "Period {$p}",
                'period_no' => $p,
                'start_time' => sprintf('%02d:00', 8 + $p),
                'end_time' => sprintf('%02d:45', 8 + $p),
                'is_break' => ($p === 3),
                'shift_type' => 'regular',
            ]);
        }
    }

    public function test_master_classwise_shows_institute_header_only_on_first_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/schedule/print/master-classwise');

        $response->assertStatus(200);
        $content = $response->getContent();

        $pages = explode('<div class="sheet-page">', $content);
        array_shift($pages);

        $this->assertGreaterThanOrEqual(2, count($pages), 'Expected at least 2 pages for 15 classes');

        // Page 0 (First Page) MUST have school-header-table and NOT continuation-header-table
        $this->assertStringContainsString('school-header-table', $pages[0]);
        $this->assertStringContainsString('Springfield Academy', $pages[0]);
        $this->assertStringNotContainsString('continuation-header-table', $pages[0]);

        // Page 1+ (Continuation Pages) MUST have continuation-header-table and NOT school-header-table
        for ($i = 1; $i < count($pages); $i++) {
            $this->assertStringContainsString('continuation-header-table', $pages[$i]);
            $this->assertStringContainsString('(continued)', $pages[$i]);
            $this->assertStringNotContainsString('school-header-table', $pages[$i]);
        }
    }

    public function test_master_teacherwise_shows_institute_header_only_on_first_page(): void
    {
        // Create 20 teachers so that chunking (10 per page) creates at least 2 pages
        for ($i = 1; $i <= 20; $i++) {
            $t = User::factory()->create([
                'name' => "Teacher {$i}",
                'role' => 'teacher',
            ]);
            \Illuminate\Support\Facades\DB::table('session_user')->insert([
                'user_id' => $t->id,
                'academic_session_id' => $this->session->id,
                'is_active' => true,
                'allowed_shifts' => 'regular',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/admin/schedule/print/master-teacherwise');

        $response->assertStatus(200);
        $content = $response->getContent();

        $pages = explode('<div class="sheet-page">', $content);
        array_shift($pages);

        $this->assertGreaterThanOrEqual(2, count($pages), 'Expected at least 2 pages for teachers');

        // First page has school header, not continuation
        $this->assertStringContainsString('school-header-table', $pages[0]);
        $this->assertStringContainsString('Springfield Academy', $pages[0]);
        $this->assertStringNotContainsString('continuation-header-table', $pages[0]);

        // Subsequent pages have continuation header
        for ($i = 1; $i < count($pages); $i++) {
            $this->assertStringContainsString('continuation-header-table', $pages[$i]);
            $this->assertStringContainsString('(continued)', $pages[$i]);
            $this->assertStringNotContainsString('school-header-table', $pages[$i]);
        }
    }

    public function test_daywise_master_teacherwise_shows_header_per_day_not_every_page(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $t = User::factory()->create([
                'name' => "Teacher {$i}",
                'role' => 'teacher',
            ]);
            \Illuminate\Support\Facades\DB::table('session_user')->insert([
                'user_id' => $t->id,
                'academic_session_id' => $this->session->id,
                'is_active' => true,
                'allowed_shifts' => 'regular',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/admin/schedule/print/daywise/master-teacherwise');

        $response->assertStatus(200);
        $content = $response->getContent();

        $pages = explode('<div class="sheet-page">', $content);
        array_shift($pages);

        $this->assertGreaterThanOrEqual(15, count($pages), 'Expected 5 days x 3 pages = 15 pages');

        // With 20 teachers chunked by 9, each day has 3 pages:
        // Page 0 (school-header), Page 1 (continuation), Page 2 (continuation)
        foreach ($pages as $index => $page) {
            $isFirstPageOfDay = ($index % 3 === 0);
            if ($isFirstPageOfDay) {
                $this->assertStringContainsString('school-header-table', $page);
                $this->assertStringNotContainsString('continuation-header-table', $page);
            } else {
                $this->assertStringContainsString('continuation-header-table', $page);
                $this->assertStringContainsString('(continued)', $page);
                $this->assertStringNotContainsString('school-header-table', $page);
            }
        }
    }

    public function test_daywise_master_classwise_shows_header_per_day_not_every_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/schedule/print/daywise/master-classwise');

        $response->assertStatus(200);
        $content = $response->getContent();

        $pages = explode('<div class="sheet-page">', $content);
        array_shift($pages);

        $this->assertGreaterThanOrEqual(10, count($pages), 'Expected 5 days x 2 pages = 10 pages');

        // In 15 classes with 9 per page, each day has 2 pages: page 0 (school-header), page 1 (continuation)
        foreach ($pages as $index => $page) {
            $isFirstPageOfDay = ($index % 2 === 0);
            if ($isFirstPageOfDay) {
                $this->assertStringContainsString('school-header-table', $page);
                $this->assertStringNotContainsString('continuation-header-table', $page);
            } else {
                $this->assertStringContainsString('continuation-header-table', $page);
                $this->assertStringContainsString('(continued)', $page);
                $this->assertStringNotContainsString('school-header-table', $page);
            }
        }
    }
}

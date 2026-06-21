<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed a default active license for all tests so they don't get blocked
        \Illuminate\Support\Facades\Cache::put(\App\Services\LicenseStatus::CACHE_KEY, [
            'stage' => \App\Services\LicenseStatus::STAGE_ACTIVE,
            'reason' => 'active',
            'days_left' => 365,
            'plan' => 'premium',
            'school_name' => 'Test School',
        ]);
    }
}

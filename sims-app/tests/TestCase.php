<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        if (!getenv('OPENSSL_CONF') && file_exists('C:\\Program Files\\SIMS\\runtime\\php\\extras\\ssl\\openssl.cnf')) {
            putenv('OPENSSL_CONF=C:\\Program Files\\SIMS\\runtime\\php\\extras\\ssl\\openssl.cnf');
        }

        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        // Ironclad safety: Force testing database to in-memory SQLite regardless of cached config
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('cache.default', 'array');

        return $app;
    }

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

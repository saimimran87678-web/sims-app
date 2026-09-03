<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache application routes, configuration, views, and events for production deployment';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('⚡ Starting SIMS Production Optimization Pipeline...');

        $this->info('📦 Caching configuration...');
        $this->call('config:cache');

        $this->info('🛣️ Caching routes...');
        $this->call('route:cache');

        $this->info('🎨 Caching Blade views...');
        $this->call('view:cache');

        $this->info('📡 Caching event listeners...');
        $this->call('event:cache');

        $this->info('🎉 SIMS application successfully optimized for production serving!');
        return 0;
    }
}

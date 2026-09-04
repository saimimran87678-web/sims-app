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

        $this->info('⚡ Syncing Livewire static assets...');
        $this->call('livewire:publish', ['--assets' => true]);
        if (!file_exists(public_path('livewire'))) {
            @mkdir(public_path('livewire'), 0775, true);
        }
        @copy(public_path('vendor/livewire/livewire.js'), public_path('livewire/livewire.js'));
        @copy(public_path('vendor/livewire/livewire.min.js'), public_path('livewire/livewire.min.js'));
        @copy(public_path('vendor/livewire/livewire.esm.js'), public_path('livewire/livewire.esm.js'));

        @chmod(database_path(), 0777);
        if (file_exists(database_path('database.sqlite'))) {
            @chmod(database_path('database.sqlite'), 0666);
        }
        @chmod(storage_path(), 0777);
        @chmod(base_path('bootstrap/cache'), 0777);

        $this->info('🎉 SIMS application successfully optimized for production serving!');
        return 0;
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin Account
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@sims.com',
            'password' => bcrypt('password'), // explicitly setting password for clarity
            'role' => 'admin',
        ]);

        // Teacher Account
        User::factory()->create([
            'name' => 'Teacher User',
            'email' => 'teacher@sims.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        
        // Test Class - REMOVED as per user request
        // \Illuminate\Support\Facades\DB::table('classes')->insert([
        //     'name' => 'Class 9',
        //     'numeric_value' => 9,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        $this->call(RolesAndPermissionsSeeder::class);
    }
}

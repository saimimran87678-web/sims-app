<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('whatsapp:process-queue')->everyMinute()->withoutOverlapping();

// Periodic background sync for licenses & telemetry heartbeat every 5 minutes
Schedule::call(function () {
    \App\Services\LicenseSyncService::syncBackground();
})->everyFiveMinutes()->name('sims-license-heartbeat')->withoutOverlapping();

// Daily SIMS Database Backup & Integrity Check at 01:00 AM
Schedule::command('db:backup')->dailyAt('01:00')->withoutOverlapping();

// Daily SIMS Auto-Update & Maintenance at 02:00 AM
Schedule::command('sims:update')->dailyAt('02:00')->withoutOverlapping();

// Daily PII Retention Purge: Clean processed WhatsApp messages older than 30 days at 03:30 AM
Schedule::command('whatsapp:purge-queue --days=30')->dailyAt('03:30')->withoutOverlapping();

// ---------------------------------------------------------------------------
// Curriculum & Architecture Testing Commands
// ---------------------------------------------------------------------------
Artisan::command('curriculum:clear-classes', function () {
    $clearedClassPrograms = \Illuminate\Support\Facades\DB::table('class_programs')->delete();
    $clearedEnrollmentTracks = \Illuminate\Support\Facades\DB::table('enrollments')->whereNotNull('program_id')->update(['program_id' => null]);
    $clearedStudentSubjects = \Illuminate\Support\Facades\DB::table('student_subject')->delete();

    $this->info("✓ Cleared {$clearedClassPrograms} class-program assignments.");
    $this->info("✓ Reset {$clearedEnrollmentTracks} student enrollment track bindings.");
    $this->info("✓ Cleared {$clearedStudentSubjects} student subject overrides.");
    $this->comment("All classes now have 0 program/subject assignments for clean testing.");
    $this->comment("The 17 Canonical Subjects and 8 Program Templates remain intact in the database.");
})->purpose('Remove all subject and program assignments from classes for clean testing');

Artisan::command('curriculum:restore-defaults', function () {
    $midGenId  = \Illuminate\Support\Facades\DB::table('programs')->where('code', 'MID-GEN')->value('id');
    $sscBioId  = \Illuminate\Support\Facades\DB::table('programs')->where('code', 'SSC-BIO')->value('id');
    $sscCsId   = \Illuminate\Support\Facades\DB::table('programs')->where('code', 'SSC-CS')->value('id');
    $hsscMedId = \Illuminate\Support\Facades\DB::table('programs')->where('code', 'HSSC-MED')->value('id');
    $hsscEngId = \Illuminate\Support\Facades\DB::table('programs')->where('code', 'HSSC-ENG')->value('id');
    $hsscIcsId = \Illuminate\Support\Facades\DB::table('programs')->where('code', 'HSSC-ICS')->value('id');

    $classes = \Illuminate\Support\Facades\DB::table('classes')->get();
    $attached = 0;

    foreach ($classes as $class) {
        $grade = (int) $class->numeric_value;
        if ($grade >= 1 && $grade <= 8 && $midGenId) {
            \Illuminate\Support\Facades\DB::table('class_programs')->insertOrIgnore([
                'class_id' => $class->id,
                'program_id' => $midGenId,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $attached++;
        } elseif ($grade === 9 || $grade === 10) {
            if ($sscCsId) {
                \Illuminate\Support\Facades\DB::table('class_programs')->insertOrIgnore([
                    'class_id' => $class->id,
                    'program_id' => $sscCsId,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $attached++;
            }
            if ($sscBioId) {
                \Illuminate\Support\Facades\DB::table('class_programs')->insertOrIgnore([
                    'class_id' => $class->id,
                    'program_id' => $sscBioId,
                    'is_default' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $attached++;
            }
        } elseif ($grade === 11 || $grade === 12) {
            if ($hsscMedId) {
                \Illuminate\Support\Facades\DB::table('class_programs')->insertOrIgnore([
                    'class_id' => $class->id,
                    'program_id' => $hsscMedId,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $attached++;
            }
            if ($hsscEngId) {
                \Illuminate\Support\Facades\DB::table('class_programs')->insertOrIgnore([
                    'class_id' => $class->id,
                    'program_id' => $hsscEngId,
                    'is_default' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $attached++;
            }
            if ($hsscIcsId) {
                \Illuminate\Support\Facades\DB::table('class_programs')->insertOrIgnore([
                    'class_id' => $class->id,
                    'program_id' => $hsscIcsId,
                    'is_default' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $attached++;
            }
        }
    }

    $this->info("✓ Restored default class-program offerings ({$attached} track attachments across " . count($classes) . " classes).");
})->purpose('Restore standard default program and subject offerings to all classes');


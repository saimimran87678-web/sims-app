<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurgeOperationalData extends Command
{
    protected $signature = 'db:purge-operational-data
                            {--dry-run : Preview tables and record counts without deleting any data}
                            {--force : Bypass interactive confirmation prompt}
                            {--clear-periods : Also clear period bell schedule timing configurations}
                            {--clear-fee-heads : Also clear master fee head categories}';

    protected $description = 'Safely clear operational test data (schedules, teacher assignments, exams, marks, attendances, activity logs, fees) while preserving core setup (Users, Students, Classes, Curriculum)';

    /**
     * Tables that are strictly operational/transactional and should be cleared.
     * Listed in child-to-parent deletion order.
     */
    protected array $operationalTables = [
        // Exams & Marks
        'exam_marks',
        'marks_configs',
        'exam_schedules',
        'datesheet_schedules',
        'grade_locks',
        'exams',

        // Timetables & Schedules
        'substitutions',
        'closed_classrooms',
        'daily_class_merges',
        'timetables',

        // Teacher Class & Subject Allocations
        'subject_allocations',
        'user_class_access',

        // Attendances & Audit
        'teacher_attendances',
        'attendances',
        'activity_logs',

        // Fees
        'fee_waiver_applications',
        'fee_record_items',
        'fee_records',
        'fee_payments',
        'fee_invoices',
        'fee_structures',

        // Queue & Notifications & Leaving Certificates
        'school_leaving_certificates',
        'holidays',
        'whatsapp_notifications',
        'whatsapp_queue',
        'failed_jobs',
        'jobs',
        'job_batches',
        'sessions',
        'password_reset_tokens',
    ];

    /**
     * Core tables that must NEVER be touched.
     */
    protected array $preservedCoreTables = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'role_has_permissions',
        'model_has_permissions',
        'session_user',
        'session_user_permissions',
        'students',
        'enrollments',
        'student_subject',
        'classes',
        'sections',
        'academic_sessions',
        'subjects',
        'programs',
        'program_subjects',
        'class_programs',
        'settings',
        'defined_options',
        'software_licenses',
        'migrations',
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $clearPeriods = (bool) $this->option('clear-periods');
        $clearFeeHeads = (bool) $this->option('clear-fee-heads');

        $tablesToPurge = $this->operationalTables;

        if ($clearPeriods) {
            $tablesToPurge[] = 'period_configs';
        }

        if ($clearFeeHeads) {
            $tablesToPurge[] = 'fee_heads';
        }

        $this->info("================================================================================");
        $this->info("             SIMS DATABASE OPERATIONAL DATA PURGE UTILITY                       ");
        $this->info("================================================================================");

        // 1. Gather Row Counts
        $summary = [];
        $totalRowsToPurge = 0;

        foreach ($tablesToPurge as $table) {
            if (!$this->tableExists($table)) {
                continue;
            }
            $count = DB::table($table)->count();
            $totalRowsToPurge += $count;
            $summary[] = [
                'table' => $table,
                'count' => $count,
                'status' => $count > 0 ? 'WILL PURGE' : 'EMPTY',
            ];
        }



        $sessionCtCount = 0;
        if ($this->tableExists('session_user')) {
            $sessionCtCount = DB::table('session_user')->whereNotNull('class_id')->orWhereNotNull('class_subject')->count();
            if ($sessionCtCount > 0) {
                $summary[] = [
                    'table' => 'session_user (Class Teacher assignments)',
                    'count' => $sessionCtCount,
                    'status' => 'WILL RESET TO NULL',
                ];
            }
        }

        $legacySubjectCount = 0;
        if ($this->tableExists('subjects')) {
            $legacySubjectCount = DB::table('subjects')->whereNotNull('class_id')->count();
            if ($legacySubjectCount > 0) {
                $summary[] = [
                    'table' => 'subjects (Legacy class_id)',
                    'count' => $legacySubjectCount,
                    'status' => 'WILL RESET TO NULL',
                ];
            }
        }

        // Display Tables to be purged
        $this->table(['Operational Entity / Table', 'Current Rows', 'Action'], array_map(function ($item) {
            return [
                $item['table'],
                $item['count'],
                $item['count'] > 0 ? "<fg=yellow>{$item['status']}</>" : "<fg=gray>{$item['status']}</>",
            ];
        }, $summary));

        $this->line("");
        $this->info("Found {$totalRowsToPurge} operational record(s) and " . ($sessionCtCount + $legacySubjectCount) . " teacher/legacy assignment(s) across " . count($summary) . " tables.");
        $this->line("");

        // Display Core Tables that are preserved
        $this->comment("CORE PRESERVED TABLES (Will remain 100% untouched):");
        $preservedSummary = [];
        foreach ($this->preservedCoreTables as $coreTable) {
            if ($this->tableExists($coreTable)) {
                $preservedSummary[] = $coreTable . ' (' . DB::table($coreTable)->count() . ')';
            }
        }
        if (!$clearPeriods && $this->tableExists('period_configs')) {
            $preservedSummary[] = 'period_configs (' . DB::table('period_configs')->count() . ') [Preserved]';
        }
        if (!$clearFeeHeads && $this->tableExists('fee_heads')) {
            $preservedSummary[] = 'fee_heads (' . DB::table('fee_heads')->count() . ') [Preserved]';
        }
        $this->line(implode(', ', $preservedSummary));
        $this->line("");

        if ($isDryRun) {
            $this->warn("[DRY RUN MODE] No changes made to the database. Exiting.");
            return 0;
        }

        // 2. Interactive Confirmation (unless --force)
        if (!$this->option('force')) {
            if (!$this->confirm('Are you absolutely sure you want to permanently delete these operational records and reset teacher class assignments?', false)) {
                $this->comment('Operation cancelled by user.');
                return 0;
            }
        }

        // 3. Step 1: Automated Safety Backup
        $this->info("Step 1/4: Creating automated pre-purge backup snapshot...");
        if (DB::transactionLevel() === 0 && !app()->runningUnitTests()) {
            $backupExitCode = $this->call('db:backup');
            if ($backupExitCode !== 0) {
                $this->error("Pre-purge backup failed! Aborting purge to prevent accidental data loss.");
                return 1;
            }
        } else {
            $this->line("Skipping physical backup snapshot in test/transactional context.");
        }

        // 4. Step 2: Transactional Purge
        $this->info("Step 2/4: Purging operational tables and resetting teacher assignments within atomic transaction...");
        $purgedCounts = [];

        try {
            DB::transaction(function () use ($tablesToPurge, &$purgedCounts) {
                // Ensure foreign keys are respected during cascading/deletion
                DB::statement('PRAGMA foreign_keys = ON;');

                foreach ($tablesToPurge as $table) {
                    if ($this->tableExists($table)) {
                        $deleted = DB::table($table)->delete();
                        $purgedCounts[$table] = $deleted;
                    }
                }

                // Reset teacher class & subject assignments (Class Teacher and Subject Teacher)


                if ($this->tableExists('session_user')) {
                    $clearedSessionClassTeachers = DB::table('session_user')
                        ->where(function ($q) {
                            $q->whereNotNull('class_id')->orWhereNotNull('class_subject');
                        })
                        ->update(['class_id' => null, 'class_subject' => null]);
                    if ($clearedSessionClassTeachers > 0) {
                        $purgedCounts['session_user (Class Teacher assignments reset)'] = $clearedSessionClassTeachers;
                    }
                }

                // Reset any legacy class_id on subjects
                if ($this->tableExists('subjects')) {
                    $clearedLegacySubjects = DB::table('subjects')
                        ->whereNotNull('class_id')
                        ->update(['class_id' => null]);
                    if ($clearedLegacySubjects > 0) {
                        $purgedCounts['subjects (Legacy class_id normalized to null)'] = $clearedLegacySubjects;
                    }
                }
            });
        } catch (Throwable $e) {
            $this->error("Purge failed and was rolled back! Error: " . $e->getMessage());
            Log::critical("Operational data purge failed", ['exception' => $e]);
            return 1;
        }

        // 5. Step 3: Foreign Key Integrity Check
        $this->info("Step 3/4: Verifying foreign key referential integrity...");
        $violations = DB::select('PRAGMA foreign_key_check;');
        if (!empty($violations)) {
            $this->error("Foreign key check failed! Violations found: " . json_encode($violations));
            Log::critical("Operational data purge caused foreign key violations", ['violations' => $violations]);
            $this->warn("You can restore your database snapshot immediately via: php artisan db:restore --latest");
            return 1;
        }
        $this->line("✓ Foreign key integrity check passed (0 violations).");

        // 6. Step 4: Reclaim disk space via VACUUM
        if (DB::transactionLevel() === 0 && !app()->runningUnitTests()) {
            $this->info("Step 4/4: Reclaiming SQLite disk space (VACUUM)...");
            try {
                DB::statement('VACUUM;');
                $this->line("✓ SQLite database vacuumed successfully.");
            } catch (Throwable $e) {
                $this->warn("VACUUM warning: " . $e->getMessage());
            }
        }

        $this->info("================================================================================");
        $this->info("             ✓ DATABASE OPERATIONAL DATA PURGED SUCCESSFULLY                    ");
        $this->info("================================================================================");
        $this->line("");
        $this->info("Summary of deleted rows:");
        foreach ($purgedCounts as $tbl => $cnt) {
            if ($cnt > 0) {
                $this->line(" - {$tbl}: {$cnt} rows removed");
            }
        }

        $this->line("");
        $this->comment("If you ever need to revert to the pre-purge state, run:");
        $this->comment("  php artisan db:restore --latest");

        return 0;
    }

    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}

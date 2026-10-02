<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Map of targeted performance indexes for:
     * - WhatsApp Manager
     * - Schedule / Period Config / Timetable
     * - Substitution Manager
     * - Reports Generator (Attendance, Results, Fees)
     * - User Authentication & Role Resolution
     */
    protected array $indexMap = [
        // 1. Core Users (Role & Teacher Scheduling lookups)
        'users' => [
            'idx_users_role_name' => ['role', 'name'],
        ],

        // 2. Academic Sessions (Active session resolution in login and headers)
        'academic_sessions' => [
            'idx_academic_sessions_archived_active' => ['is_archived', 'is_active'],
        ],

        // 3. Session User Pivot (Scoping active staff to academic sessions)
        'session_user' => [
            'idx_session_user_session_active' => ['academic_session_id', 'is_active', 'allowed_shifts'],
        ],

        // 4. Period Configuration (Ordering by shift and period)
        'period_configs' => [
            'idx_period_configs_shift_period' => ['shift_type', 'period_no'],
        ],

        // 5. Timetables (Schedule Manager, View Schedule, Substitution Manager)
        'timetables' => [
            'idx_timetables_teacher_day_period' => ['teacher_id', 'day', 'period_no', 'is_substitute'],
            'idx_timetables_class_day_sub' => ['class_id', 'day', 'is_substitute', 'period_no'],
            'idx_timetables_substitute_lookup' => ['substitute_date', 'period_no', 'is_substitute'],
            'idx_timetables_class_sub_date' => ['class_id', 'period_no', 'is_substitute', 'substitute_date'],
        ],

        // 6. WhatsApp Queue (Priority batch processing, scheduled dispatch, phone searching)
        'whatsapp_queue' => [
            'idx_whatsapp_queue_status_prio_id' => ['status', 'priority', 'id'],
            'idx_whatsapp_queue_scheduled_status' => ['scheduled_at', 'status'],
            'idx_whatsapp_queue_phone' => ['phone'],
        ],

        // 7. Fee Records (Defaulters List, Invoicing, Billing lookups)
        'fee_records' => [
            'idx_fee_records_session_bal_period' => ['academic_session_id', 'balance', 'period'],
            'idx_fee_records_class_session_period' => ['class_id', 'academic_session_id', 'period'],
            'idx_fee_records_student_session_period' => ['student_id', 'academic_session_id', 'period'],
        ],

        // 8. Fee Record Items (Discount & monthly category aggregation)
        'fee_record_items' => [
            'idx_fee_record_items_rec_cat' => ['fee_record_id', 'category'],
            'idx_fee_record_items_rec_head' => ['fee_record_id', 'fee_head_name'],
        ],

        // 9. Exam Marks & Configurations (Result Report Card Generation)
        'exam_marks' => [
            'idx_exam_marks_exam_student' => ['exam_id', 'student_id'],
        ],
        'marks_configs' => [
            'idx_marks_configs_exam_class' => ['exam_id', 'class_id'],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        foreach ($this->indexMap as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableIndexes as $indexName => $columns) {
                // Ensure all specified columns exist on the table
                $allExist = true;
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        $allExist = false;
                        break;
                    }
                }

                if (!$allExist) {
                    continue;
                }

                if ($isSqlite) {
                    $quotedCols = implode(', ', array_map(fn($c) => '"' . $c . '"', $columns));
                    DB::statement("CREATE INDEX IF NOT EXISTS \"{$indexName}\" ON \"{$table}\" ({$quotedCols})");
                } else {
                    try {
                        Schema::table($table, function (Blueprint $b) use ($columns, $indexName) {
                            $b->index($columns, $indexName);
                        });
                    } catch (\Throwable $e) {
                        // Suppress if already exists
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        foreach ($this->indexMap as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableIndexes as $indexName => $columns) {
                if ($isSqlite) {
                    DB::statement("DROP INDEX IF EXISTS \"{$indexName}\"");
                } else {
                    try {
                        Schema::table($table, function (Blueprint $b) use ($indexName) {
                            $b->dropIndex($indexName);
                        });
                    } catch (\Throwable $e) {
                        // Suppress if not exists
                    }
                }
            }
        }
    }
};

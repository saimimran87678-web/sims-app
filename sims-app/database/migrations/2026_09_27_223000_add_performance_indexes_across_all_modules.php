<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Map of tables and their respective performance indexes.
     */
    protected array $indexMap = [
        // 1. Authentication, Unlocking & Global Session Resolution
        'users' => [
            'idx_users_role_active' => ['role', 'is_active'],
        ],
        'academic_sessions' => [
            'idx_academic_sessions_active' => ['is_active'],
        ],
        'session_user_permissions' => [
            'idx_session_user_perms_lookup' => ['academic_session_id', 'shift_type'],
        ],

        // 2. Class Navigation & Subject Lookups
        'classes' => [
            'idx_classes_session_shift_num' => ['academic_session_id', 'shift_type', 'numeric_value'],
        ],
        'subjects' => [
            'idx_subjects_class_id' => ['class_id'],
        ],

        // 3. Student Management & Enrolment Directory
        'enrollments' => [
            'idx_enrollments_session_class_shift' => ['academic_session_id', 'class_id', 'shift_type', 'status'],
            'idx_enrollments_session_status' => ['academic_session_id', 'status'],
            'idx_enrollments_student_id' => ['student_id'],
        ],
        'students' => [
            'idx_students_name' => ['name'],
            'idx_students_status' => ['status'],
        ],

        // 4. Attendance Registers (Student, Teacher, Holidays)
        'attendances' => [
            'idx_attendances_date' => ['date'],
            'idx_attendances_session_date_status' => ['academic_session_id', 'date', 'status'],
        ],
        'teacher_attendances' => [
            'idx_teacher_attendances_session_date' => ['academic_session_id', 'date', 'shift_type'],
        ],
        'holidays' => [
            'idx_holidays_session_shift_dates' => ['academic_session_id', 'shift_type', 'start_date', 'end_date'],
        ],

        // 5. Fee Management (Vouchers, Defaulters, Payments)
        'fee_records' => [
            'idx_fee_records_session_status' => ['academic_session_id', 'status'],
            'idx_fee_records_class_status' => ['class_id', 'status'],
            'idx_fee_records_due_date' => ['due_date'],
            'idx_fee_records_student_status' => ['student_id', 'status'],
        ],
        'fee_payments' => [
            'idx_fee_payments_record_id' => ['fee_record_id'],
            'idx_fee_payments_student_id' => ['student_id'],
            'idx_fee_payments_payment_date' => ['payment_date'],
        ],
        'fee_structures' => [
            'idx_fee_structures_session_class' => ['academic_session_id', 'class_id'],
        ],
        'fee_record_items' => [
            'idx_fee_record_items_record_id' => ['fee_record_id'],
        ],

        // 6. Exams, Marks & Schedules
        'exams' => [
            'idx_exams_session_active' => ['academic_session_id', 'is_active'],
        ],
        'exam_marks' => [
            'idx_exam_marks_exam_subject' => ['exam_id', 'subject_id'],
            'idx_exam_marks_student_id' => ['student_id'],
        ],
        'exam_schedules' => [
            'idx_exam_schedules_exam_class' => ['exam_id', 'class_id'],
        ],

        // 7. Schedules & Timetables
        'timetables' => [
            'idx_timetables_class_day' => ['class_id', 'day'],
            'idx_timetables_subject_id' => ['subject_id'],
        ],

        // 8. Communication Hub & WhatsApp Queue
        'whatsapp_queue' => [
            'idx_whatsapp_queue_status_prio' => ['status', 'priority'],
        ],
        'whatsapp_notifications' => [
            'idx_whatsapp_notif_student_status' => ['student_id', 'status'],
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

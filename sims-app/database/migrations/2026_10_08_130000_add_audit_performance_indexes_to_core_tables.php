<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexSafely('sections', ['class_id'], 'idx_sections_class_id');
        $this->addIndexSafely('students', ['section_id'], 'idx_students_section_id');
        $this->addIndexSafely('fee_invoices', ['student_id'], 'idx_fee_invoices_student_id');
        $this->addIndexSafely('academic_sessions', ['shift_type'], 'idx_academic_sessions_shift_type');
        $this->addIndexSafely('fee_structures', ['shift_type'], 'idx_fee_structures_shift_type');
        $this->addIndexSafely('marks_configs', ['academic_session_id', 'subject_id'], 'idx_marks_configs_session_subject');
        $this->addIndexSafely('subject_allocations', ['user_id'], 'idx_subject_allocations_user_id');
        $this->addIndexSafely('substitutions', ['subject_id', 'status'], 'idx_substitutions_subject_status');
        $this->addIndexSafely('teacher_attendances', ['status'], 'idx_teacher_attendances_status');
        $this->addIndexSafely('users', ['class_id'], 'idx_users_class_id');
        $this->addIndexSafely('fee_heads', ['academic_session_id'], 'idx_fee_heads_session_id');
        $this->addIndexSafely('software_licenses', ['status'], 'idx_software_licenses_status');
        $this->addIndexSafely('session_user', ['class_id'], 'idx_session_user_class_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexSafely('sections', 'idx_sections_class_id');
        $this->dropIndexSafely('students', 'idx_students_section_id');
        $this->dropIndexSafely('fee_invoices', 'idx_fee_invoices_student_id');
        $this->dropIndexSafely('academic_sessions', 'idx_academic_sessions_shift_type');
        $this->dropIndexSafely('fee_structures', 'idx_fee_structures_shift_type');
        $this->dropIndexSafely('marks_configs', 'idx_marks_configs_session_subject');
        $this->dropIndexSafely('subject_allocations', 'idx_subject_allocations_user_id');
        $this->dropIndexSafely('substitutions', 'idx_substitutions_subject_status');
        $this->dropIndexSafely('teacher_attendances', 'idx_teacher_attendances_status');
        $this->dropIndexSafely('users', 'idx_users_class_id');
        $this->dropIndexSafely('fee_heads', 'idx_fee_heads_session_id');
        $this->dropIndexSafely('software_licenses', 'idx_software_licenses_status');
        $this->dropIndexSafely('session_user', 'idx_session_user_class_id');
    }

    private function addIndexSafely(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) return;

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) return;
        }

        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Index already exists or SQLite duplicate index ignored
        }
    }

    private function dropIndexSafely(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) return;

        try {
            Schema::table($table, function (Blueprint $t) use ($indexName) {
                $t->dropIndex($indexName);
            });
        } catch (\Throwable $e) {
            // Index doesn't exist
        }
    }
};

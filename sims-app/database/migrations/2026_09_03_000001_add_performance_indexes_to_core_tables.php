<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('session_user_permissions', function (Blueprint $table) {
            $table->index(['user_id', 'academic_session_id', 'shift_type'], 'idx_sup_user_session_shift');
        });

        Schema::table('whatsapp_queue', function (Blueprint $table) {
            $table->index(['student_id', 'status'], 'idx_wq_student_status');
            $table->index(['status', 'scheduled_at'], 'idx_wq_status_scheduled');
        });

        Schema::table('fee_records', function (Blueprint $table) {
            $table->index(['academic_session_id', 'student_id', 'period'], 'idx_fr_session_student_period');
            $table->index(['academic_session_id', 'status'], 'idx_fr_session_status');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['academic_session_id', 'student_id', 'status'], 'idx_enr_session_student_status');
            $table->index(['academic_session_id', 'class_id', 'shift_type'], 'idx_enr_session_class_shift');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['academic_session_id', 'student_id', 'date'], 'idx_att_session_student_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_user_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_sup_user_session_shift');
        });

        Schema::table('whatsapp_queue', function (Blueprint $table) {
            $table->dropIndex('idx_wq_student_status');
            $table->dropIndex('idx_wq_status_scheduled');
        });

        Schema::table('fee_records', function (Blueprint $table) {
            $table->dropIndex('idx_fr_session_student_period');
            $table->dropIndex('idx_fr_session_status');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enr_session_student_status');
            $table->dropIndex('idx_enr_session_class_shift');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_att_session_student_date');
        });
    }
};

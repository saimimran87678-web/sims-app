<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('father_cnic', 15)->nullable()->index()->after('father_name');
            $table->string('guardian_cnic', 15)->nullable()->index()->after('father_cnic');
            $table->string('guardian_relation', 50)->nullable()->after('guardian_cnic');
            $table->boolean('is_guardian_primary')->default(false)->after('guardian_relation');
            $table->string('sibling_concession_status', 25)->default('none')->after('is_guardian_primary');
            $table->decimal('custom_concession_amount', 10, 2)->nullable()->after('sibling_concession_status');

            $table->index(['father_cnic', 'status'], 'idx_students_father_cnic_status');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_father_cnic_status');
            $table->dropIndex(['father_cnic']);
            $table->dropIndex(['guardian_cnic']);
            $table->dropColumn([
                'father_cnic',
                'guardian_cnic',
                'guardian_relation',
                'is_guardian_primary',
                'sibling_concession_status',
                'custom_concession_amount',
            ]);
        });
    }
};

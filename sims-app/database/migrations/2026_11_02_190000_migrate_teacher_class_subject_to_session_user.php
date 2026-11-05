<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reconcile any existing legacy class_id and class_subject into session_user
        if (Schema::hasColumn('users', 'class_id') || Schema::hasColumn('users', 'class_subject')) {
            $activeSessionId = DB::table('academic_sessions')->where('is_active', 1)->value('id')
                ?? DB::table('academic_sessions')->orderByDesc('id')->value('id');

            if ($activeSessionId) {
                $legacyUsers = DB::table('users')
                    ->whereNotNull('class_id')
                    ->orWhereNotNull('class_subject')
                    ->get();

                foreach ($legacyUsers as $u) {
                    $pivot = DB::table('session_user')
                        ->where('user_id', $u->id)
                        ->where('academic_session_id', $activeSessionId)
                        ->first();

                    if ($pivot) {
                        DB::table('session_user')
                            ->where('id', $pivot->id)
                            ->update([
                                'class_id' => $pivot->class_id ?? $u->class_id,
                                'class_subject' => $pivot->class_subject ?? $u->class_subject,
                            ]);
                    } else {
                        DB::table('session_user')->insert([
                            'user_id' => $u->id,
                            'academic_session_id' => $activeSessionId,
                            'class_id' => $u->class_id,
                            'class_subject' => $u->class_subject,
                            'is_active' => true,
                            'is_primary' => true,
                            'allowed_shifts' => 'both',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 2. Drop legacy columns class_id and class_subject from users table
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::statement('DROP INDEX IF EXISTS idx_users_class_id;');
            DB::statement('
                CREATE TABLE "users_new" (
                    "id" integer primary key autoincrement not null,
                    "name" varchar not null,
                    "email" varchar not null,
                    "role" varchar not null default (\'teacher\'),
                    "email_verified_at" datetime,
                    "password" varchar not null,
                    "remember_token" varchar,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "is_active" tinyint(1) not null default \'1\'
                );
            ');
            DB::statement('
                INSERT INTO "users_new" ("id", "name", "email", "role", "email_verified_at", "password", "remember_token", "created_at", "updated_at", "is_active")
                SELECT "id", "name", "email", "role", "email_verified_at", "password", "remember_token", "created_at", "updated_at", "is_active" FROM "users";
            ');
            DB::statement('DROP TABLE "users";');
            DB::statement('ALTER TABLE "users_new" RENAME TO "users";');
            DB::statement('CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");');
            DB::statement('CREATE INDEX "idx_users_role_name" ON "users" ("role", "name");');
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'class_id')) {
                    $table->dropForeign(['class_id']);
                    $table->dropColumn('class_id');
                }
                if (Schema::hasColumn('users', 'class_subject')) {
                    $table->dropColumn('class_subject');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'class_id')) {
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'class_subject')) {
                $table->string('class_subject')->nullable();
            }
        });
    }
};

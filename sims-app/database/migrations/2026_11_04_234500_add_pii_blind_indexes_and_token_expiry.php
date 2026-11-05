<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add blind index hash columns to students
        Schema::table('students', function (Blueprint $table) {
            $table->string('father_cnic_hash', 64)->nullable()->index()->after('father_cnic');
            $table->string('guardian_cnic_hash', 64)->nullable()->index()->after('guardian_cnic');
            $table->string('b_form_hash', 64)->nullable()->index()->after('b_form_number');
            $table->string('phone_hash', 64)->nullable()->index()->after('phone');
        });

        // 2. Add access_token_expires_at to fee_records
        Schema::table('fee_records', function (Blueprint $table) {
            $table->dateTime('access_token_expires_at')->nullable()->index()->after('access_token');
        });

        // 3. Encrypt existing plaintext student data and calculate blind index hashes
        $students = DB::table('students')->get();
        $appKey = config('app.key');

        foreach ($students as $student) {
            $updates = [];

            // Helper to check if already encrypted (Laravel Crypt payload is JSON with 'iv', 'value', 'mac')
            $isEncrypted = function ($val) {
                if (empty($val) || !is_string($val)) return false;
                $decoded = json_decode(base64_decode($val), true);
                return is_array($decoded) && isset($decoded['iv'], $decoded['value'], $decoded['mac']);
            };

            $hashBlind = function ($val) use ($appKey) {
                if (empty($val)) return null;
                $clean = preg_replace('/\D/', '', (string) $val);
                if (empty($clean)) {
                    $clean = strtolower(trim((string) $val));
                }
                return hash_hmac('sha256', $clean, $appKey);
            };

            // father_cnic
            if (!empty($student->father_cnic)) {
                $rawFatherCnic = $student->father_cnic;
                $updates['father_cnic_hash'] = $hashBlind($rawFatherCnic);
                if (!$isEncrypted($rawFatherCnic)) {
                    $updates['father_cnic'] = Crypt::encryptString($rawFatherCnic);
                }
            }

            // guardian_cnic
            if (!empty($student->guardian_cnic)) {
                $rawGuardianCnic = $student->guardian_cnic;
                $updates['guardian_cnic_hash'] = $hashBlind($rawGuardianCnic);
                if (!$isEncrypted($rawGuardianCnic)) {
                    $updates['guardian_cnic'] = Crypt::encryptString($rawGuardianCnic);
                }
            }

            // b_form_number
            if (!empty($student->b_form_number)) {
                $rawBForm = $student->b_form_number;
                $updates['b_form_hash'] = $hashBlind($rawBForm);
                if (!$isEncrypted($rawBForm)) {
                    $updates['b_form_number'] = Crypt::encryptString($rawBForm);
                }
            }

            // phone
            if (!empty($student->phone)) {
                $rawPhone = $student->phone;
                $updates['phone_hash'] = $hashBlind($rawPhone);
                if (!$isEncrypted($rawPhone)) {
                    $updates['phone'] = Crypt::encryptString($rawPhone);
                }
            }

            // address
            if (!empty($student->address) && !$isEncrypted($student->address)) {
                $updates['address'] = Crypt::encryptString($student->address);
            }

            if (!empty($updates)) {
                DB::table('students')->where('id', $student->id)->update($updates);
            }
        }

        // 4. Backfill default expiration for existing fee_records (30 days from creation)
        DB::table('fee_records')
            ->whereNotNull('access_token')
            ->whereNull('access_token_expires_at')
            ->update([
                'access_token_expires_at' => now()->addDays(30),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Decrypt student fields back to plaintext if possible
        $students = DB::table('students')->get();
        foreach ($students as $student) {
            $updates = [];
            foreach (['father_cnic', 'guardian_cnic', 'b_form_number', 'phone', 'address'] as $col) {
                if (!empty($student->$col)) {
                    try {
                        $updates[$col] = Crypt::decryptString($student->$col);
                    } catch (\Throwable $e) {
                        // ignore if was not encrypted
                    }
                }
            }
            if (!empty($updates)) {
                DB::table('students')->where('id', $student->id)->update($updates);
            }
        }

        // 2. Drop columns
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['father_cnic_hash']);
            $table->dropIndex(['guardian_cnic_hash']);
            $table->dropIndex(['b_form_hash']);
            $table->dropIndex(['phone_hash']);
            $table->dropColumn(['father_cnic_hash', 'guardian_cnic_hash', 'b_form_hash', 'phone_hash']);
        });

        Schema::table('fee_records', function (Blueprint $table) {
            $table->dropIndex(['access_token_expires_at']);
            $table->dropColumn('access_token_expires_at');
        });
    }
};

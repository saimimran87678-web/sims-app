<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Injects 15 teachers with Pakistani names starting with "Mr."
     * and binds them to the active academic session with the Teacher Spatie role.
     */
    public function run(): void
    {
        // 1. Resolve active session
        $session = AcademicSession::where('is_active', true)->first()
            ?? AcademicSession::firstOrCreate(
                ['name' => '2026-2027'],
                [
                    'start_date' => now()->startOfYear(),
                    'end_date'   => now()->endOfYear(),
                    'is_active'  => true,
                    'shift_type' => 'Regular',
                ]
            );

        $sessionId = $session->id;
        $shiftType = ($session->shift_type === 'Regular') ? 'regular' : 'morning';

        // 2. Ensure Teacher Spatie role exists
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        // 3. Define 15 Pakistani teachers
        $teachers = [
            [
                'name'  => 'Mr. Muhammad Tariq',
                'email' => 'mr.tariq@sims.local',
            ],
            [
                'name'  => 'Mr. Abdul Rehman',
                'email' => 'mr.rehman@sims.local',
            ],
            [
                'name'  => 'Mr. Rashid Mehmood',
                'email' => 'mr.rashid@sims.local',
            ],
            [
                'name'  => 'Mr. Imran Khan',
                'email' => 'mr.imran@sims.local',
            ],
            [
                'name'  => 'Mr. Shahzad Ahmed',
                'email' => 'mr.shahzad@sims.local',
            ],
            [
                'name'  => 'Mr. Farhan Ali',
                'email' => 'mr.farhan@sims.local',
            ],
            [
                'name'  => 'Mr. Asif Javed',
                'email' => 'mr.asif@sims.local',
            ],
            [
                'name'  => 'Mr. Usman Bilal',
                'email' => 'mr.usman@sims.local',
            ],
            [
                'name'  => 'Mr. Waqas Ashraf',
                'email' => 'mr.waqas@sims.local',
            ],
            [
                'name'  => 'Mr. Kamran Siddiqui',
                'email' => 'mr.kamran@sims.local',
            ],
            [
                'name'  => 'Mr. Naveed Akhtar',
                'email' => 'mr.naveed@sims.local',
            ],
            [
                'name'  => 'Mr. Zeeshan Haider',
                'email' => 'mr.zeeshan@sims.local',
            ],
            [
                'name'  => 'Mr. Sajid Hussain',
                'email' => 'mr.sajid@sims.local',
            ],
            [
                'name'  => 'Mr. Rizwan Ullah',
                'email' => 'mr.rizwan@sims.local',
            ],
            [
                'name'  => 'Mr. Babar Azam',
                'email' => 'mr.babar@sims.local',
            ],
        ];

        DB::beginTransaction();
        try {
            $defaultPassword = Hash::make('password123');

            foreach ($teachers as $data) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name'              => $data['name'],
                        'password'          => $defaultPassword,
                        'role'              => 'teacher',
                        'email_verified_at' => now(),
                        'is_active'         => true,
                    ]
                );

                // Ensure name and role are consistent if already existed
                if ($user->name !== $data['name'] || $user->role !== 'teacher') {
                    $user->update([
                        'name'      => $data['name'],
                        'role'      => 'teacher',
                        'is_active' => true,
                    ]);
                }

                // Assign Teacher role via Spatie
                if (!$user->hasRole('Teacher')) {
                    $user->assignRole($teacherRole);
                }

                // Associate with active session in session_user pivot
                DB::table('session_user')->updateOrInsert(
                    [
                        'user_id'             => $user->id,
                        'academic_session_id' => $sessionId,
                    ],
                    [
                        'is_active'      => true,
                        'is_primary'     => true,
                        'allowed_shifts' => $shiftType,
                        'updated_at'     => now(),
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixClassTeacherMigration extends Command
{
    protected $signature = 'fix:class-teachers';
    protected $description = 'Fix class teacher assignments by correctly mapping users.class_id to the right session_user record based on the classes table academic_session_id.';

    public function handle()
    {
        $users = DB::table('users')->whereNotNull('class_id')->get();
        $count = 0;

        foreach ($users as $user) {
            // Find which session the class actually belongs to
            $classSessionId = DB::table('classes')->where('id', $user->class_id)->value('academic_session_id');

            if ($classSessionId) {
                // Update the session_user record for THAT specific session
                DB::table('session_user')
                    ->where('user_id', $user->id)
                    ->where('academic_session_id', $classSessionId)
                    ->update([
                        'class_id' => $user->class_id,
                        'class_subject' => property_exists($user, 'class_subject') ? $user->class_subject : null,
                    ]);
                $count++;
            }
        }

        $this->info("Successfully restored $count class teacher assignments to their proper sessions.");
    }
}

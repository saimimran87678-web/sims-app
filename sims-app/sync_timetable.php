<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Timetable;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    $days = ['Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    // Count entries to show how many will be affected
    foreach (['Monday', ...$days] as $d) {
        $count = Timetable::where('day', $d)->count();
        echo "Entries for $d: $count\n";
    }

    echo "Deleting non-Monday entries...\n";
    Timetable::whereIn('day', $days)->delete();

    echo "Fetching Monday entries...\n";
    $mondayEntries = Timetable::where('day', 'Monday')->get();
    
    echo "Copying " . $mondayEntries->count() . " entries to other days...\n";
    
    $inserts = [];
    foreach ($mondayEntries as $entry) {
        foreach ($days as $day) {
            $inserts[] = [
                'schedule_template_id' => $entry->schedule_template_id,
                'day' => $day,
                'period_no' => $entry->period_no,
                'class_id' => $entry->class_id,
                'teacher_id' => $entry->teacher_id,
                'subject_id' => $entry->subject_id,
                'room' => $entry->room,
                'is_divided' => $entry->is_divided,
                'merged_class_id' => $entry->merged_class_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }
    
    // Insert in chunks to avoid memory issues
    $chunks = array_chunk($inserts, 500);
    foreach ($chunks as $chunk) {
        Timetable::insert($chunk);
    }

    DB::commit();
    echo "Successfully synchronized timetable across all days based on Monday.\n";

    // Verify
    foreach (['Monday', ...$days] as $d) {
        $count = Timetable::where('day', $d)->count();
        echo "New entries for $d: $count\n";
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

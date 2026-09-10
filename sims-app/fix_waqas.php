<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$waqasId = 21;
$classId = 23; // 9C
$periodNo = 6;

// Get the Monday entry for Waqas to copy the subject
$mondayEntry = App\Models\Timetable::where('class_id', $classId)
    ->where('period_no', $periodNo)
    ->where('day', 'Monday')
    ->first();

if (!$mondayEntry) {
    echo "Monday entry not found\n";
    exit;
}

$days = ['Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

foreach ($days as $day) {
    // Find existing entry
    $entry = App\Models\Timetable::where('class_id', $classId)
        ->where('period_no', $periodNo)
        ->where('day', $day)
        ->first();
        
    if ($entry) {
        $entry->teacher_id = $waqasId;
        $entry->subject_id = $mondayEntry->subject_id;
        $entry->save();
        echo "Updated $day\n";
    } else {
        // Create if missing
        App\Models\Timetable::create([
            'schedule_template_id' => $mondayEntry->schedule_template_id,
            'day' => $day,
            'period_no' => $periodNo,
            'class_id' => $classId,
            'teacher_id' => $waqasId,
            'subject_id' => $mondayEntry->subject_id,
            'room' => $mondayEntry->room,
            'is_divided' => $mondayEntry->is_divided,
            'merged_class_id' => $mondayEntry->merged_class_id,
        ]);
        echo "Created $day\n";
    }
}
echo "Done.\n";

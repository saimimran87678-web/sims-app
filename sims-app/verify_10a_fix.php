<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Timetable;
use App\Models\Classes;
use App\Models\ClosedClassroom;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

// 1. Setup
$date = Carbon::parse('2026-02-18'); // Wednesday (The problem day)
$day = $date->format('l');

// Find Class 10A (ID 2 usually)
$class10A = Classes::where('name', 'LIKE', '%10A%')->first();
if (!$class10A) die("Class 10A not found.\n");

// Find Mr. Safeerullah
$teacher = User::where('name', 'LIKE', '%Safeerullah%')->first();
if (!$teacher) die("Teacher not found.\n");

echo "Test Date: " . $date->toDateString() . " ($day)\n";
echo "Teacher: {$teacher->name} (ID: {$teacher->id})\n";
echo "Class 10A: {$class10A->name} (ID: {$class10A->id})\n";

// 2. Helper
function getClassCountInSubstitution($teacherId, $date) {
    echo "--- Opening Modal Logic ---\n";
    $component = new \App\Livewire\Admin\TeacherAttendanceManager();
    $component->mount(); 
    $component->date = $date->toDateString();
    $component->manageForTeacherId = $teacherId;

    $component->openSubstitutionModal($teacherId);
    
    // Debug: Print class IDs found
    $foundClassIds = [];
    foreach ($component->substitutionData as $data) {
        $foundClassIds[] = $data['timetable']->class_id;
    }
    echo "Debug: Found Class IDs in Modal: " . implode(',', $foundClassIds) . "\n";

    return count($component->substitutionData);
}

// 3. Ensure 10A is CLOSED
ClosedClassroom::updateOrCreate(
    ['class_id' => $class10A->id, 'date' => $date->toDateString()],
    ['reason' => 'Test Closure']
);

$closedCount = ClosedClassroom::where('date', $date->toDateString())->where('class_id', $class10A->id)->count();
echo "Confirmed 10A is closed in DB: $closedCount\n";

// 4. Run Test
$count = getClassCountInSubstitution($teacher->id, $date);

// Check if 10A is present (ID 2) in the log output above.
// If my fix works, 10A (ID 2) should NOT be in the list.

// 5. Cleanup? Leave it for manual verification if needed, or delete.
// ClosedClassroom::where('class_id', $class10A->id)->delete();

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Livewire\Admin\TeacherAttendanceManager;
use Livewire\Livewire;
use App\Models\ClosedClassroom;

$date = '2026-02-23';

try {
    // Clear existing closed classes for this date to start fresh
    ClosedClassroom::where('date', $date)->delete();

    $component = Livewire::test(TeacherAttendanceManager::class, ['date' => $date]);
    
    echo "Testing Bulk Closure for School (6-10)...\n";
    $component->set('closeClassId', 'school_all');
    $component->set('closeClassReason', 'School Holiday');
    $component->call('saveCloseClass');
    
    $closedCount = ClosedClassroom::where('date', $date)->count();
    echo "Total classes closed: $closedCount\n";

    $classes = \App\Models\Classes::all();
    $schoolClasses = $classes->filter(function($c) {
        if (preg_match('/(\d+)/', $c->name, $matches)) {
            $grade = (int)$matches[1];
            return $grade >= 6 && $grade <= 10;
        }
        return false;
    });

    $collegeClasses = $classes->filter(function($c) {
        if (preg_match('/(\d+)/', $c->name, $matches)) {
            $grade = (int)$matches[1];
            return $grade == 11 || $grade == 12;
        }
        return false;
    });

    $schoolClosedCount = ClosedClassroom::where('date', $date)->whereIn('class_id', $schoolClasses->pluck('id'))->count();
    $collegeClosedCount = ClosedClassroom::where('date', $date)->whereIn('class_id', $collegeClasses->pluck('id'))->count();

    echo "School classes closed: $schoolClosedCount / " . $schoolClasses->count() . "\n";
    echo "College classes closed: $collegeClosedCount / " . $collegeClasses->count() . "\n";

    if ($schoolClosedCount === $schoolClasses->count() && $collegeClosedCount === 0) {
        echo "[PASS] Bulk closure for School worked correctly!\n";
    } else {
        echo "[FAIL] Bulk closure logic check failed.\n";
    }

    echo "\nTesting Bulk Closure for College (11-12)...\n";
    $component->set('closeClassId', 'college_all');
    $component->call('saveCloseClass');

    $collegeClosedCount = ClosedClassroom::where('date', $date)->whereIn('class_id', $collegeClasses->pluck('id'))->count();
    echo "College classes closed: $collegeClosedCount / " . $collegeClasses->count() . "\n";

    if ($collegeClosedCount === $collegeClasses->count()) {
        echo "[PASS] Bulk closure for College worked correctly!\n";
    } else {
        echo "[FAIL] Bulk closure logic for College failed.\n";
    }

} catch (\Exception $e) {
    echo "Error during verification: " . $e->getMessage() . "\n";
}

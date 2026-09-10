<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Livewire\Admin\TeacherAttendanceManager;
use Livewire\Livewire;

$teacherId = 20; // Mr. Aftab
$date = '2026-02-23';

try {
    $component = Livewire::test(TeacherAttendanceManager::class, ['date' => $date]);
    
    // Simulate opening the substitution modal
    $component->call('openSubstitutionModal', $teacherId);
    
    $substitutionData = $component->get('substitutionData');
    $substitutions = $component->get('substitutions');

    echo "Verification Results for Mr. Aftab (Teacher ID: $teacherId) on $date:\n";

    // List all found periods
    echo "\nFound Periods in Substitution Data:\n";
    foreach ($substitutionData as $data) {
        echo "Period: {$data['period_no']}, Class(es): {$data['class_names']}\n";
    }

    // Check for Period 2 grouping
    $period2 = collect($substitutionData)->firstWhere('period_no', 2);
    if ($period2 && count($period2['timetable_ids']) === 2) {
        echo "[PASS] Period 2 correctly grouped (Class 11B + Class 11C).\n";
    } else {
        echo "[FAIL] Period 2 grouping failed.\n";
    }

    // Check if Class 10B is present (should be hidden now)
    $has10B = collect($substitutionData)->filter(function($data) {
        return str_contains($data['class_names'], '10B');
    })->isNotEmpty();

    if (!$has10B) {
        echo "[PASS] Class 10B is correctly hidden from the substitution list.\n";
    } else {
        echo "[FAIL] Class 10B is STILL visible in the substitution list.\n";
    }

    // Simulate saving a substitution for Period 2
    $substituteId = 1; // Assuming ID 1 exists
    $component->set('substitutions.period_2', $substituteId);
    $component->call('saveSubstitutions');
    
    // Verify in database
    $subRecords = \Illuminate\Support\Facades\DB::table('substitutions')
        ->where('date', $date)
        ->whereIn('timetable_id', $period2['timetable_ids'])
        ->get();
        
    if ($subRecords->count() === 2) {
        echo "[PASS] Substitution saved correctly for both classes in Period 2.\n";
    } else {
        echo "[FAIL] Substitution not saved correctly for combined periods. Found: " . $subRecords->count() . "\n";
    }

    // Check Arrangements logic
    $arrangements = $component->get('arrangements');
    echo "\nArrangements Data Check:\n";
    
    $aftabArr = collect($arrangements)->firstWhere('teacher_name', 'Mr. Aftab Ahmed');
    if ($aftabArr) {
        $classes = collect($aftabArr['classes']);
        $p2Arr = $classes->firstWhere('period_no', 2);
        
        if ($p2Arr) {
            echo "Period 2 Arrangement Class Name: {$p2Arr['class_name']}\n";
            if ($p2Arr['class_name'] === '11(B+C)') {
                echo "[PASS] Period 2 arrangement correctly formatted as 11(B+C).\n";
            } else {
                echo "[FAIL] Period 2 arrangement name mismatch. Expected 11(B+C), got: {$p2Arr['class_name']}\n";
            }
        } else {
            echo "[FAIL] Period 2 arrangement not found for Mr. Aftab.\n";
        }
    } else {
        echo "[FAIL] Mr. Aftab not found in arrangements data.\n";
    }

} catch (\Exception $e) {
    echo "Error during verification: " . $e->getMessage() . "\n";
}

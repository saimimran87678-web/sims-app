<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$date = '2026-02-10'; // Or Carbon::now()->format('Y-m-d');

echo "Checking substitution counts for $date\n";

// Emulate logic from openSubstitutionModal
$counts = DB::table('substitutions')
            ->where('date', $date)
            ->select('substitute_teacher_id', DB::raw('count(*) as count'))
            ->groupBy('substitute_teacher_id')
            ->pluck('count', 'substitute_teacher_id')
            ->toArray();

print_r($counts);

// Check specific teacher if known (e.g. Mr. Javed id 25)
$javedId = 25;
if (isset($counts[$javedId])) {
    echo "Mr. Javed (ID $javedId) has " . $counts[$javedId] . " substitutions.\n";
} else {
    echo "Mr. Javed (ID $javedId) has 0 substitutions.\n";
}

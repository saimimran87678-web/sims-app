<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Classes;
use App\Models\User;
use App\Models\ScheduleTemplate;
use App\Models\Subject;

echo "Classes Count: " . Classes::count() . "\n";
if (Classes::count() > 0) {
    echo "First Class: " . Classes::first()->name . "\n";
}

echo "Teachers Count: " . User::where('role', 'teacher')->count() . "\n";

echo "Templates Count: " . ScheduleTemplate::count() . "\n";
if (ScheduleTemplate::count() > 0) {
    echo "First Template: " . ScheduleTemplate::first()->name . " (Active: " . (ScheduleTemplate::first()->is_active ? 'Yes' : 'No') . ")\n";
}

echo "Subjects Count: " . Subject::count() . "\n";

<?php 
$users = \App\Models\User::where('role', 'teacher')->get();
foreach ($users as $user) {
    // Regex to match "Mr.", "Mr. ", "Mr ", "mr.", etc.
    $newName = preg_replace('/^Mr\.?\s*/i', '', $user->name);
    // Also remove "Ms." or "Mrs." just in case? The user specifically asked for "Mr"
    $newName = preg_replace('/^Mr\.?\s*/i', '', $newName); // run again just in case? no.

    if ($newName !== $user->name) {
        echo "Renaming: {$user->name} -> {$newName}\n";
        $user->name = $newName;
        $user->save();
    }
}
echo "Name stripping finished.\n";

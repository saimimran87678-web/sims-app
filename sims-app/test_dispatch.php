<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$admin = User::first();
if ($admin) {
    $admin->role = 'admin';
    Auth::login($admin);
}

$urls = [
    '/admin/schedule/print/master-classwise',
    '/admin/schedule/print/master-teacherwise',
    '/admin/schedule/print/class/1',
    '/admin/schedule/print/teacher/1',
    '/admin/schedule/print/teachers-bulk',
];

foreach ($urls as $url) {
    $req = Request::create($url, 'GET');
    // Set authenticated user on request
    $req->setUserResolver(fn() => $admin);
    
    $response = $kernel->handle($req);
    echo "URL: $url -> Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() >= 400) {
        echo "Response snippet: " . substr($response->getContent(), 0, 300) . "\n\n";
    }
}
unlink(__FILE__);

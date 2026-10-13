<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Đây là nơi đăng ký các route cho giao diện Web (Trình duyệt).
| Đối với dự án Game API, chúng ta chỉ dùng để check trạng thái Server.
*/

$healthHandler = function () {
    $dbConnected = false;
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbConnected = true;
    } catch (\Throwable $e) {
        $dbConnected = false;
    }

    return response()->json([
        'project'         => 'MAGIC HOP - GAME API',
        'status'          => $dbConnected ? 'Healthy' : 'Degraded',
        'database_status' => $dbConnected ? 'online' : 'offline',
        'version'         => '1.0.0',
        'environment'     => app()->environment(),
        'timezone'        => config('app.timezone'),
        'timestamp'       => now()->toIso8601String(),
    ]);
};

Route::get('/', $healthHandler)->withoutMiddleware('web');
Route::match(['get', 'head'], '/index.php', $healthHandler)->withoutMiddleware('web');


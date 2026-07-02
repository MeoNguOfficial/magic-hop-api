<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Đây là nơi đăng ký các route cho giao diện Web (Trình duyệt).
| Đối với dự án Game API, chúng ta chỉ dùng để check trạng thái Server.
*/

Route::get('/', function () {
    return response()->json([
        'project'     => 'MAGIC HOP - GAME API',
        'status'      => 'Healthy',
        'version'     => '1.0.0',
        'environment' => app()->environment(),
        'timezone'    => config('app.timezone'),
        'timestamp'   => now()->toIso8601String(),
    ]);
});

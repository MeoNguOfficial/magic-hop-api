<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Bắt lỗi khi mất kết nối Database hoặc sai thông tin đăng nhập DB (Custom JSON)
        $exceptions->render(function (\PDOException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Hệ thống đang bảo trì hoặc máy chủ dữ liệu (Database) không phản hồi. Vui lòng thử lại sau.',
                    'error_code' => 'DATABASE_CONNECTION_ERROR'
                ], 500);
            }
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, Request $request) {
            // Kiểm tra các mã lỗi liên quan đến kết nối (Ví dụ: Connection refused, Access denied)
            if (str_contains($e->getMessage(), 'SQLSTATE[HY000]') || str_contains($e->getMessage(), 'Connection refused')) {
                if ($request->is('api/*') || $request->expectsJson()) {
                    return response()->json([
                        'message' => 'Lỗi kết nối cơ sở dữ liệu. Dịch vụ tạm thời gián đoạn.',
                        'error_code' => 'DATABASE_CONNECTION_ERROR'
                    ], 500);
                }
            }
        });
    })->create();

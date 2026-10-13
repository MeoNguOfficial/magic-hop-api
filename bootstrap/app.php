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
            fn (Request $request) => true, // Luôn ưu tiên trả về JSON cho toàn bộ API & Web service
        );

        $dbOfflineResponse = function () {
            return response()->json([
                'status'          => 'error',
                'message'         => 'Database is offline. Hệ thống đang bảo trì hoặc máy chủ dữ liệu không phản hồi. Vui lòng thử lại sau.',
                'error_code'      => 'DATABASE_CONNECTION_ERROR',
                'database_status' => 'offline',
            ], 500);
        };

        // 1. Bắt lỗi mất kết nối từ PDOException (Timeout, sai thông tin đăng nhập, host offline)
        $exceptions->render(function (\PDOException $e, Request $request) use ($dbOfflineResponse) {
            return $dbOfflineResponse();
        });

        // 2. Bắt lỗi truy vấn khi kết nối DB bị đứt gãy
        $exceptions->render(function (\Illuminate\Database\QueryException $e, Request $request) use ($dbOfflineResponse) {
            $msg = $e->getMessage();
            $isConnectionError = $e->getPrevious() instanceof \PDOException
                || str_contains($msg, 'SQLSTATE[HY000]')
                || str_contains($msg, 'SQLSTATE[08006]')
                || str_contains($msg, 'SQLSTATE[08001]')
                || str_contains($msg, 'Connection refused')
                || str_contains($msg, 'Connection timed out')
                || str_contains($msg, 'A connection attempt failed')
                || str_contains($msg, 'Access denied')
                || str_contains($msg, 'No connection could be made')
                || str_contains($msg, 'getaddrinfo failed');

            if ($isConnectionError) {
                return $dbOfflineResponse();
            }
        });

        // 3. Bắt lỗi FatalError nếu PHP chạm timeout kết nối DB
        $exceptions->render(function (\Symfony\Component\ErrorHandler\Error\FatalError $e, Request $request) use ($dbOfflineResponse) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Connector.php') || (str_contains($msg, 'execution time') && str_contains($msg, 'Database'))) {
                return $dbOfflineResponse();
            }
        });
    })->create();

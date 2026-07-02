<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Đọc Header 'Accept-Language' từ Client gửi lên (mặc định là 'vi' nếu không truyền)
        $locale = $request->header('Accept-Language', config('app.locale'));

        // Chỉ chấp nhận các ngôn ngữ hệ thống hỗ trợ
        if (in_array($locale, ['en', 'vi'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}

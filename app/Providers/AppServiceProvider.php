<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tuỳ chỉnh bộ xác thực Token của Sanctum (Sliding Session)
        // Token sẽ tự động vô hiệu hoá nếu người dùng không tương tác API (offline) quá 30 ngày
        \Laravel\Sanctum\Sanctum::authenticateAccessTokensUsing(
            function (\Laravel\Sanctum\PersonalAccessToken $accessToken, bool $isValid) {
                // Nếu token còn hiệu lực cơ bản, kiểm tra thêm thời gian online cuối cùng (last_used_at)
                if ($isValid && $accessToken->last_used_at) {
                    if ($accessToken->last_used_at->copy()->addDays(30)->isPast()) {
                        // Vượt quá 30 ngày không hoạt động -> Thu hồi token lập tức (Auto Logout ở Backend)
                        $accessToken->delete();
                        return false;
                    }
                }
                return $isValid;
            }
        );
    }
}

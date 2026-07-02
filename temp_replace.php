<?php
$files = ["d:/MeoTN Games/MagicHopGame/magic-hop-api/app/Http/Controllers/AuthController.php", "d:/MeoTN Games/MagicHopGame/magic-hop-api/app/Http/Controllers/UserController.php"];
foreach ($files as $file) {
    $content = file_get_contents($file);
    // Replace __("key") ?? "string" with __("key")
    $content = preg_replace("/(__\([^\)]+\))\s*\?\?\s*\'[^\']*\'/", "$1", $content);
    $content = preg_replace('/(__\([^\)]+\))\s*\?\?\s*\"[^\"]*\"/', '$1', $content);

    // Some specific ones in AuthController:
    $content = str_replace(
        '__("Tài khoản của bạn tạm thời bị khóa. Vui lòng thử lại sau {$minutesLeft} phút.")',
        "__('api.auth.locked_temp', ['minutes' => \$minutesLeft])",
        $content
    );
    $content = str_replace(
        '__(\'Tài khoản của bạn đã bị khóa vĩnh viễn do nhập sai quá nhiều lần. Vui lòng liên hệ hỗ trợ.\')',
        "__('api.auth.locked_permanently')",
        $content
    );
    $content = str_replace(
        '__(\'Tài khoản của bạn đã bị khóa vĩnh viễn do nhập sai 11 lần.\')',
        "__('api.auth.locked_permanently_11')",
        $content
    );
    $content = str_replace(
        '__("Sai mật khẩu quá nhiều lần. Tài khoản bị khóa tạm thời trong {$lockoutMinutes} phút.")',
        "__('api.auth.wrong_password_lock_warning', ['minutes' => \$lockoutMinutes])",
        $content
    );
    $content = str_replace(
        '__("Mật khẩu không chính xác. Bạn còn {$remainingAttempts} lần thử trước khi tài khoản bị khóa tạm thời.")',
        "__('api.auth.wrong_password_attempts_left', ['attempts' => \$remainingAttempts])",
        $content
    );
    $content = str_replace(
        "'message' => 'Lấy thông tin tài khoản thành công!'",
        "'message' => __('api.user.retrieved')",
        $content
    );
    $content = str_replace(
        "'message' => 'Hệ thống gửi mail đang bận, vui lòng thử lại sau.'",
        "'message' => __('api.auth.mail_busy')",
        $content
    );
    $content = str_replace(
        "'message' => 'Không thể kết nối đến tổng đài gửi mã OTP, thử lại sau.'",
        "'message' => __('api.auth.mail_failed')",
        $content
    );
    
    // In UserController:
    $content = str_replace(
        "'message' => 'Bạn không có quyền thực hiện hành động này.'",
        "'message' => __('api.auth.forbidden')",
        $content
    );
    $content = str_replace(
        "'message' => 'User updated successfully'",
        "'message' => __('api.user.updated')",
        $content
    );

    // Also need to handle UserController __('api.user.created') ?? '...' which is already handled by regex.

    file_put_contents($file, $content);
}
echo "Done replacing";

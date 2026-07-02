<?php

use App\Models\User;
use App\Models\Beatmap;
use App\Models\UserScore;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Console Routes (Artisan Commands)
|--------------------------------------------------------------------------
| Định nghĩa các câu lệnh gõ qua Terminal bằng cú pháp: php artisan <tên_lệnh>
*/

// 1. Lệnh mặc định của Laravel (Để test console hoạt động tốt)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Hiển thị một câu truyền cảm hứng ngẫu nhiên');


/**
 * 2. Lệnh CLI: Khóa tài khoản (Ban) người chơi có thời hạn hoặc vĩnh viễn
 * Cú pháp chạy: php artisan user:ban {identity?} {duration?} {reason?*}
 * Hỗ trợ: p (vĩnh viễn), 1d (ngày), 2h (giờ), 30m (phút), 2w (tuần), 1mo (tháng)
 */
Artisan::command('user:ban {identity?} {duration?} {reason?*}', function () {
    $identity = $this->argument('identity');
    $duration = $this->argument('duration');

    // NẾU THIẾU THAM SỐ: Hiển thị Hướng dẫn cú pháp đầy màu sắc
    if (!$identity || !$duration) {
        $this->line("<fg=red;options=bold>❌ LỖI CÚ PHÁP: Thiếu thông tin người chơi hoặc thời hạn khóa tài khoản!</>");
        $this->line("");
        $this->line("<fg=yellow;options=bold>💡 HƯỚNG DẪN CÚ PHÁP SỬ DỤNG LỆNH BAN TÀI KHOẢN:</>");
        $this->line("  <fg=cyan;options=bold>php artisan user:ban {identity} {duration} {reason}</>");
        $this->line("");
        $this->line("<fg=gray>Trong đó:</>");
        $this->line("  - <fg=green;options=bold>{identity}</> : ID (mã số), Email, Username hoặc Số điện thoại của người chơi cần ban.");
        $this->line("  - <fg=green;options=bold>{duration}</> : Thời hạn cấm.");
        $this->line("       • Gõ <fg=white>p</> hoặc <fg=white>perm</> hoặc <fg=white>permanent</> để <fg=red;options=bold>CẤM VĨNH VIỄN</>.");
        $this->line("       • Gõ số kèm chữ để cấm theo thời gian: <fg=white>1d</> (1 ngày), <fg=white>2h</> (2 giờ), <fg=white>30m</> (30 phút), <fg=white>2w</> (2 tuần), <fg=white>1mo</> (1 tháng).");
        $this->line("  - <fg=green;options=bold>{reason}</>   : Lý do cấm tài khoản (Nhập tự do, hệ thống tự gom các từ lại).");
        $this->line("");
        $this->line("<fg=yellow;options=bold>Ví dụ cụ thể:</>");
        $this->line("  • Khóa 1 ngày vì gian lận:     <fg=gray>php artisan user:ban player123 1d Hack điểm số cao</>");
        $this->line("  • Khóa vĩnh viễn vì phá hoại:  <fg=gray>php artisan user:ban hack@gmail.com perm Spaming chat support</>");
        return;
    }

    // 3. Tiến hành truy quét User trong database
    $user = User::where('id', $identity)
        ->orWhere('username', $identity)
        ->orWhere('email', $identity)
        ->orWhere('phone', $identity)
        ->first();

    if (!$user) {
        $this->error("❌ Không tìm thấy bất kỳ tài khoản người chơi nào khớp với thông tin: '{$identity}'");
        return;
    }

    // Tài khoản là Admin -> Chặn và báo đỏ
    if ($user->is_admin) {
        $this->error("🚫 CẢNH BÁO BẢO MẬT: Không thể khóa (Ban) tài khoản có quyền Quản trị viên (Admin)!");
        return;
    }

    // Ghép mảng lý do (reason) thành chuỗi thống nhất
    $reasonArray = $this->argument('reason');
    $reason = !empty($reasonArray) ? implode(' ', $reasonArray) : 'Phát hiện tài khoản dùng công cụ gian lận!';

    // 4. Phân tích tham số thời gian cấm (Duration) - Hỗ trợ mở rộng
    $bannedUntil = null;
    $durationText = 'Vĩnh viễn';
    $durationLower = strtolower($duration);

    if (in_array($durationLower, ['p', 'perm', 'permanent'])) {
        $bannedUntil = null; // Cấm vĩnh viễn
    } else {
        // Cấm tạm thời: Phân tích cú pháp 1d, 2h, 30m, 2w, 1mo
        preg_match('/^(\d+)(mo|w|d|h|m)$/', $durationLower, $matches);
        if (empty($matches)) {
            $this->error("❌ Sai định dạng thời gian cấm! Vui lòng dùng: 1d (ngày), 2h (giờ), 30m (phút), 2w (tuần), 1mo (tháng) hoặc 'perm' (vĩnh viễn).");
            return;
        }

        $value = (int)$matches[1];
        $unit = $matches[2];

        switch ($unit) {
            case 'mo':
                $bannedUntil = Carbon::now()->addMonths($value);
                $durationText = "{$value} tháng (đến " . $bannedUntil->format('H:i:s d/m/Y') . ")";
                break;
            case 'w':
                $bannedUntil = Carbon::now()->addWeeks($value);
                $durationText = "{$value} tuần (đến " . $bannedUntil->format('H:i:s d/m/Y') . ")";
                break;
            case 'd':
                $bannedUntil = Carbon::now()->addDays($value);
                $durationText = "{$value} ngày (đến " . $bannedUntil->format('H:i:s d/m/Y') . ")";
                break;
            case 'h':
                $bannedUntil = Carbon::now()->addHours($value);
                $durationText = "{$value} giờ (đến " . $bannedUntil->format('H:i:s d/m/Y') . ")";
                break;
            case 'm':
                $bannedUntil = Carbon::now()->addMinutes($value);
                $durationText = "{$value} phút (đến " . $bannedUntil->format('H:i:s d/m/Y') . ")";
                break;
        }
    }

    // 5. Cập nhật Database & đá tài khoản ra khỏi hệ thống ngay lập tức
    $user->update([
        'is_banned'     => true,
        'banned_until'  => $bannedUntil,
        'banned_reason' => $reason,
    ]);

    // Thu hồi (Revoke) tất cả Sanctum Tokens để đá người chơi này văng ngay lập tức khỏi game
    $user->tokens()->delete();

    // Ghi log hệ thống
    Log::warning("User Ban Log: Tài khoản '{$user->username}' (ID: {$user->id}) đã bị khóa bởi CLI. Thời hạn: {$durationText}. Lý do: {$reason}");

    // 6. In kết quả đẹp mắt ra terminal
    $this->line("");
    $this->line("<fg=red;options=bold>==================================================</>");
    $this->line("<fg=red;options=bold>🔒 THIẾT LẬP LỆNH KHÓA TÀI KHOẢN (BAN USER) THÀNH CÔNG!</>");
    $this->line(" - Người chơi:     <fg=cyan;options=bold>{$user->username}</> (Email: <fg=gray>{$user->email}</> | ID: <fg=gray>{$user->id}</>)");
    $this->line(" - Trạng thái:     <fg=red;options=bold>ĐÃ KHÓA (BANNED)</>");
    $this->line(" - Lý do:          <fg=yellow>{$reason}</>");
    $this->line(" - Thời hạn:       <fg=cyan>{$durationText}</>");
    $this->line(" - Tokens:         <fg=red>Đã thu hồi toàn bộ token đăng nhập hiện tại</>");
    $this->line("<fg=red;options=bold>==================================================</>");
})->purpose('Khóa tài khoản (Ban) người chơi tạm thời hoặc vĩnh viễn kèm lý do phạt');


/**
 * 3. Lệnh CLI: Mở khóa tài khoản (Unban) người chơi
 * Cú pháp chạy: php artisan user:unban {identity}
 */
Artisan::command('user:unban {identity?}', function () {
    $identity = $this->argument('identity');

    if (!$identity) {
        $this->error("❌ LỖI CÚ PHÁP: Vui lòng nhập ID, Email, Username hoặc Số điện thoại người chơi để mở khóa!");
        $this->line("  Cú pháp chuẩn: <fg=cyan>php artisan user:unban {identity}</>");
        $this->line("  Ví dụ: <fg=gray>php artisan user:unban player_one</>");
        return;
    }

    $user = User::where('id', $identity)
        ->orWhere('username', $identity)
        ->orWhere('email', $identity)
        ->orWhere('phone', $identity)
        ->first();

    if (!$user) {
        $this->error("❌ Không tìm thấy người chơi nào khớp với thông tin: '{$identity}'");
        return;
    }

    if (!$user->is_banned) {
        $this->info("ℹ️ Người chơi '{$user->username}' hiện đang hoạt động bình thường, không cần mở khóa.");
        return;
    }

    $user->update([
        'is_banned'     => false,
        'banned_until'  => null,
        'banned_reason' => null,
    ]);

    // Ghi log hệ thống
    Log::info("User Unban Log: Tài khoản '{$user->username}' (ID: {$user->id}) đã được gỡ khóa thành công qua CLI.");

    $this->line("");
    $this->line("<fg=green;options=bold>==================================================</>");
    $this->line("<fg=green;options=bold>🔓 MỞ KHÓA TÀI KHOẢN (UNBAN) THÀNH CÔNG!</>");
    $this->line(" - Người chơi:     <fg=cyan;options=bold>{$user->username}</> (ID: <fg=gray>{$user->id}</>)");
    $this->line(" - Trạng thái:     <fg=green>Hoạt động bình thường (Đã gỡ cấm)</>");
    $this->line("<fg=green;options=bold>==================================================</>");
})->purpose('Mở khóa tài khoản (Unban) cho người chơi');


/**
 * 4. Lệnh CLI: Cấp quyền quản trị (Admin) cho người chơi
 * Cú pháp chạy: php artisan user:admin {identity?}
 */
Artisan::command('user:admin {identity?}', function () {
    $identity = $this->argument('identity');

    if (!$identity) {
        $this->error("❌ LỖI CÚ PHÁP: Thiếu tên tài khoản cần cấp quyền quản trị!");
        $this->line("  Cú pháp chuẩn: <fg=cyan>php artisan user:admin {identity}</>");
        $this->line("  Ví dụ: <fg=gray>php artisan user:admin developer_boss</>");
        return;
    }

    $user = User::where('username', $identity)
        ->orWhere('id', $identity)
        ->orWhere('email', $identity)
        ->first();

    if (!$user) {
        $this->error("❌ Không tìm thấy tài khoản người chơi: '{$identity}'");
        return;
    }

    if ($user->is_admin) {
        $this->warn("⚠️ Tài khoản '{$user->username}' đã có sẵn quyền Admin từ trước.");
        return;
    }

    $user->update(['is_admin' => true]);

    $this->line("");
    $this->line("<fg=green;options=bold>==================================================</>");
    $this->line("<fg=green;options=bold>👑 THÀNH CÔNG: ĐÃ CẤP QUYỀN QUẢN TRỊ VIÊN (ADMIN) THÀNH CÔNG!</>");
    $this->line(" - Tài khoản được nâng cấp: <fg=cyan;options=bold>{$user->username}</>");
    $this->line(" - Trạng thái:              <fg=green;options=bold>Có toàn quyền truy cập chức năng Admin</>");
    $this->line("<fg=green;options=bold>==================================================</>");
})->purpose('Cấp quyền Admin hệ thống cho một người chơi');


/**
 * 5. Lệnh CLI: Gỡ quyền quản trị (Admin) của người chơi
 * Cú pháp chạy: php artisan user:unadmin {identity?}
 */
Artisan::command('user:unadmin {identity?}', function () {
    $identity = $this->argument('identity');

    if (!$identity) {
        $this->error("❌ LỖI CÚ PHÁP: Thiếu tên tài khoản cần gỡ quyền quản trị!");
        $this->line("  Cú pháp chuẩn: <fg=cyan>php artisan user:unadmin {identity}</>");
        $this->line("  Ví dụ: <fg=gray>php artisan user:unadmin developer_boss</>");
        return;
    }

    $user = User::where('username', $identity)
        ->orWhere('id', $identity)
        ->orWhere('email', $identity)
        ->first();

    if (!$user) {
        $this->error("❌ Không tìm thấy tài khoản người chơi: '{$identity}'");
        return;
    }

    if (!$user->is_admin) {
        $this->warn("⚠️ Tài khoản '{$user->username}' vốn không có quyền Admin từ trước.");
        return;
    }

    $user->update(['is_admin' => false]);

    $this->line("");
    $this->line("<fg=green;options=bold>==================================================</>");
    $this->line("<fg=green;options=bold>✅ THÀNH CÔNG: ĐÃ GỠ QUYỀN QUẢN TRỊ VIÊN (ADMIN) THÀNH CÔNG!</>");
    $this->line(" - Tài khoản bị gỡ: <fg=cyan;options=bold>{$user->username}</>");
    $this->line(" - Trạng thái:      <fg=green;options=bold>Trở thành người chơi bình thường</>");
    $this->line("<fg=green;options=bold>==================================================</>");
})->purpose('Gỡ quyền Admin hệ thống của một người chơi');


/**
 * 6. Lệnh CLI: Reset / Xóa sạch bảng xếp hạng (Leaderboard) của một bài nhạc cụ thể
 * Cú pháp chạy: php artisan leaderboard:reset {beatmap_id?}
 */
Artisan::command('leaderboard:reset {beatmap_id?}', function () {
    $beatmapId = $this->argument('beatmap_id');

    if (!$beatmapId) {
        $this->error("❌ LỖI CÚ PHÁP: Vui lòng nhập ID bản đồ nhạc (Beatmap ID) cần dọn dẹp!");
        $this->line("  Cú pháp chuẩn: <fg=cyan>php artisan leaderboard:reset {beatmap_id}</>");
        $this->line("  Ví dụ: <fg=gray>php artisan leaderboard:reset 105</>");
        return;
    }

    $beatmap = Beatmap::find($beatmapId);

    if (!$beatmap) {
        $this->error("❌ Không tìm thấy bản đồ nhạc nào khớp với ID: '{$beatmapId}'");
        return;
    }

    // Tiến hành xóa toàn bộ điểm số kỷ lục của beatmap này trong database
    $deletedCount = UserScore::where('beatmap_id', $beatmapId)->delete();

    $this->line("");
    $this->line("<fg=green;options=bold>==================================================</>");
    $this->line("<fg=green;options=bold>🧹 THÀNH CÔNG: ĐÃ DỌN SẠCH BẢNG XẾP HẠNG (LEADERBOARD)!</>");
    $this->line(" - Bài nhạc được reset: <fg=cyan;options=bold>{$beatmap->name}</>");
    $this->line(" - ID Bài nhạc:         <fg=gray>{$beatmap->id}</>");
    $this->line(" - Số điểm đã xóa bỏ:   <fg=red;options=bold>{$deletedCount} bản ghi kỷ lục</>");
    $this->line(" - Trạng thái:          <fg=green>Bảng xếp hạng đã trở về trống rỗng (0 điểm)</>");
    $this->line("<fg=green;options=bold>==================================================</>");
})->purpose('Xóa sạch toàn bộ điểm số kỷ lục (Leaderboard) của một bài nhạc cụ thể');


/*
|--------------------------------------------------------------------------
| Task Scheduling (Tác vụ chạy ngầm tự động)
|--------------------------------------------------------------------------
*/

// Tác vụ tự động: Quét hệ thống lúc 00:00 mỗi ngày để tắt các bài nhạc đã quá ngày ẩn (day_hide)
Schedule::call(function () {
    $now = now()->toDateString();

    $expiredBeatmaps = Beatmap::where('is_available', true)
        ->whereNotNull('day_hide')
        ->where('day_hide', '<=', $now) // Sử dụng <= để bao gồm cả ngày hiện tại
        ->get();

    foreach ($expiredBeatmaps as $bm) {
        $bm->update(['is_available' => false]);
        Log::info("Auto Scheduled Task: Đã tự động ẩn bản đồ nhạc '{$bm->name}' do vượt quá ngày ẩn hiển thị ({$bm->day_hide}).");
    }
})->dailyAt('00:00')->name('auto-hide-expired-beatmaps');
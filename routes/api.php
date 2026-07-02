<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\BeatmapController;
use App\Http\Controllers\UserScoreController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ENDPOINTS (API Công Khai - Không cần Token Đăng Nhập)
|--------------------------------------------------------------------------
| - Game Client có thể gọi trực tiếp ngay từ màn hình khởi động (Loading / Login)
| - Không yêu cầu đính kèm mã bảo mật Bearer Token.
*/

// --- Quản lý Tài khoản Công Khai & Xác thực kích hoạt ---
Route::post('/register', [AuthController::class, 'register']);
// Route::post('/activate-account', [AuthController::class, 'activateAccount']); // Kích hoạt tài khoản bằng mã OTP
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// --- Quản lý Beatmaps Công Khai ---
// Các API công khai dành cho Client / Game để lấy danh sách bài nhạc công chiếu
Route::prefix('beatmaps')->group(function () {
    Route::get('/', [BeatmapController::class, 'index']);         // Lấy danh sách (Hỗ trợ ?search= keyword và ?mode=admin)
    Route::get('{id}', [BeatmapController::class, 'show']);        // Xem chi tiết một bài nhạc
});


/*
|--------------------------------------------------------------------------
| PROTECTED ENDPOINTS (API Bảo Mật - Bắt Buộc Gửi Kèm Bearer Token)
|--------------------------------------------------------------------------
| - Toàn bộ API phía dưới bắt buộc Game Client phải truyền chuỗi token ở Header:
|   Authorization: Bearer <mã_token_nhận_được_khi_login_thành_công>
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |----------------------------------------------------------------------
    | 1. Quản lý Đăng xuất & Tài khoản Cá nhân
    |----------------------------------------------------------------------
    | - Quản lý phiên làm việc và thông tin hồ sơ của người dùng.
    */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);


    /*
    |----------------------------------------------------------------------
    | 2. Cấu hình Game cá nhân (User Settings)
    |----------------------------------------------------------------------
    | - Đồng bộ hóa tùy chỉnh nút bấm, tốc độ, âm thanh của người chơi.
    */
    Route::put('/user-settings/{userId}', [UserSettingController::class, 'update']);


    /*
    |----------------------------------------------------------------------
    | 3. Quản lý Điểm số & Bảng xếp hạng Kỷ lục (Scores & Leaderboard)
    |----------------------------------------------------------------------
    | - Ghi nhận thành tích chơi game và hiển thị xếp hạng cạnh tranh.
    */
    Route::prefix('scores')->group(function () {
        Route::get('/', [UserScoreController::class, 'index']);          // Lấy lịch sử điểm số (Lọc theo user_id/beatmap_id kèm Cursor)
        Route::post('/', [UserScoreController::class, 'store']);         // Gửi điểm số lên hệ thống sau khi kết thúc ván chơi
        Route::delete('{id}', [UserScoreController::class, 'destroy']);  // Xóa điểm số khả nghi (Dành cho Admin khi phát hiện hack/cheat)
    });

    // Xem danh sách Top 10 cao thủ điểm cao nhất của bài nhạc (Đặt ngoài scores)
    Route::get('/beatmaps/{beatmapId}/leaderboard', [UserScoreController::class, 'leaderboard']);


    /*
    |----------------------------------------------------------------------
    | 4. Hệ thống Chat Hỗ trợ & Trợ lý ảo (Chat & Support Tickets)
    |----------------------------------------------------------------------
    | - Tạo yêu cầu cần hỗ trợ kỹ thuật, khôi phục tài khoản, v.v.
    | - Kích hoạt kịch bản trả lời tự động trực tiếp từ Trợ lý ảo.
    */
    Route::prefix('chat')->group(function () {
        // Tạo phòng chat / gửi yêu cầu hỗ trợ mới (Tự sinh tin nhắn Chào mừng của Bot)
        Route::post('/rooms', [ChatController::class, 'createRoom']);
        
        // Gửi tin nhắn mới vào phòng chat hỗ trợ (Hỗ trợ text và file đính kèm gửi lên ImgBB)
        Route::post('/rooms/{roomId}/messages', [ChatController::class, 'sendMessage']);
        
        // Xem lịch sử tin nhắn chi tiết của một phòng chat cụ thể (Đồng thời đánh dấu "đã đọc" tin nhắn của đối phương)
        Route::get('/rooms/{roomId}', [ChatController::class, 'showRoom']);

        // Xóa một phòng chat hỗ trợ cụ thể kèm dọn dẹp các tệp tin hình ảnh liên quan
        Route::delete('/rooms/{roomId}', [ChatController::class, 'deleteRoom']);

        // Thu hồi hoặc xóa một tin nhắn đơn lẻ trong cuộc trò chuyện (Hỗ trợ dọn dẹp ảnh cục bộ)
        Route::delete('/messages/{messageId}', [ChatController::class, 'deleteMessage']);
    });


    /*
    |----------------------------------------------------------------------
    | 5. Khu vực Admin / Creator (Quản trị Hệ thống & Dữ liệu Game)
    |----------------------------------------------------------------------
    | - Các cổng tương tác đặc biệt tối ưu cho việc vận hành máy chủ và quản lý map.
    | - Bảo vệ chặt chẽ các tài nguyên quản lý.
    */
    Route::prefix('admin')->group(function () {

        // --- Quản lý Màn chơi / Bản đồ nốt nhạc (Beatmap Management) ---
        Route::prefix('beatmaps')->group(function () {
            Route::post('/', [BeatmapController::class, 'store']);                   // Tạo map bằng form thông thường
            Route::post('import-json', [BeatmapController::class, 'createWithJson']);// Import JSON trực tiếp từ Map Editor
            Route::put('{id}', [BeatmapController::class, 'update']);                // Cập nhật thông số bài nhạc
            Route::delete('{id}', [BeatmapController::class, 'destroy']);            // Xóa hẳn bài nhạc khỏi hệ thống
        });

        // --- Quản lý & Điều hành Ticket Chat Hỗ trợ ---
        Route::prefix('chat')->group(function () {
            Route::get('/rooms', [ChatController::class, 'indexRooms']);             // Xem danh sách hàng đợi các phòng chat cần hỗ trợ
            Route::put('/rooms/{roomId}/status', [ChatController::class, 'updateStatus']); // Nhận hỗ trợ hoặc thay đổi trạng thái ticket (Close/Resolve)
        });

        // --- Lệnh điều khiển Hệ thống (System Commands) ---
        // API nhận lệnh điều khiển (Artisan, Cache, Migrate...) từ giao diện admin ngoài
        Route::post('command', [AdminController::class, 'executeCommand']);
    });

});
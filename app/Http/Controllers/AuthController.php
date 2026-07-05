<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\LoginResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Đăng ký tài khoản mới cho người chơi (Register)
     */
    public function register(Request $request)
    {
        // 1. Kiểm duyệt dữ liệu đăng ký đầu vào
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6', // Yêu cầu có trường 'password_confirmation' đi kèm
            'email'    => 'required|string|email|max:255|unique:users,email',
            
            // Chặn tuyệt đối việc gửi lên các trường hệ thống/quản trị khi đăng ký
            'is_admin'       => 'prohibited',
            'is_actived'     => 'prohibited',
            'is_locked'      => 'prohibited',
            'locked_until'   => 'prohibited',
            'login_attempts' => 'prohibited',
            'is_banned'      => 'prohibited',
            'banned_until'   => 'prohibited',
            'banned_reason'  => 'prohibited',
        ], [
            'username.required' => __('api.validation.username_required'),
            'username.unique'   => __('api.validation.username_unique'),
            'email.required'    => __('api.validation.email_required'),
            'email.unique'      => __('api.validation.email_unique'),
            'password.required' => __('api.validation.password_required'),
            'password.min'      => __('api.validation.password_min'),
            
            // Thông báo lỗi các trường cấm
            'is_admin.prohibited'       => __('api.validation.prohibited', ['attribute' => 'is_admin']),
            'is_actived.prohibited'     => __('api.validation.prohibited', ['attribute' => 'is_actived']),
            'is_locked.prohibited'      => __('api.validation.prohibited', ['attribute' => 'is_locked']),
            'locked_until.prohibited'   => __('api.validation.prohibited', ['attribute' => 'locked_until']),
            'login_attempts.prohibited' => __('api.validation.prohibited', ['attribute' => 'login_attempts']),
            'is_banned.prohibited'      => __('api.validation.prohibited', ['attribute' => 'is_banned']),
            'banned_until.prohibited'   => __('api.validation.prohibited', ['attribute' => 'banned_until']),
            'banned_reason.prohibited'  => __('api.validation.prohibited', ['attribute' => 'banned_reason']),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Tiến hành khởi tạo User mới với ULID làm khóa chính
        $user = User::create([
            'id'             => (string) Str::ulid(),
            'username'       => $request->username,
            'password'       => Hash::make($request->password),
            'email'          => $request->email,
            'is_actived'     => true, // Kích hoạt ngay sau khi đăng ký thành công để người chơi trải nghiệm game luôn
            'login_attempts' => 0,
            'is_locked'      => false,
            'is_banned'      => false,
        ]);

        // 3. Khởi tạo cấu hình game mặc định gắn với user này
        $user->setting()->create();

        // 4. Tạo token Sanctum tự động đăng nhập luôn cho Game-Client
        $token = $user->createToken($request->input('device_name', 'Game-Client'))->plainTextToken;

        return (new LoginResource($user, $token))
            ->additional(['message' => __('api.auth.register_success')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Đăng nhập và cấp Token song ngữ với cấu trúc kiểm tra khóa bảo mật nâng cao
     */
    public function login(Request $request)
    {
        // 1. Validate request kết hợp custom message song ngữ
        $loginField = $request->has('email') ? 'email' : 'username';

        $validator = Validator::make($request->all(), [
            $loginField => 'required|string',
            'password'  => 'required|string',
        ], [
            "{$loginField}.required" => __('api.validation.username_required'),
            'password.required'      => __('api.validation.password_required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loginValue = $request->input($loginField);

        // 2. Tìm user theo username hoặc email
        $user = User::where('username', $loginValue)
            ->orWhere('email', $loginValue)
            ->first();

        // Nếu không tìm thấy user, trả về lỗi luôn (tránh xử lý brute force cho user không tồn tại)
        if (!$user) {
            return response()->json(['message' => __('api.auth.failed')], 401);
        }

        // 3. Kiểm tra và tự động mở ban nếu đã hết hạn ban do admin thiết lập
        if ($user->is_banned && $user->banned_until && Carbon::now()->greaterThanOrEqualTo($user->banned_until)) {
            $user->update([
                'is_banned'     => false,
                'banned_until'  => null,
                'banned_reason' => null,
            ]);
        }

        // [TÍCH HỢP TỪ BẢN MỚI]: Gửi kèm đầy đủ thông tin thời gian cấm và lý do cấm về Client
        if ($user->is_banned) {
            return response()->json([
                'message'      => __('api.auth.banned'),
                'is_banned'    => true,
                'banned_until' => $user->banned_until ? (string) $user->banned_until : null,
                'reason'       => $user->banned_reason ?? 'Phát hiện tài khoản dùng công cụ gian lận!'
            ], 403);
        }

        // 4. Kiểm tra và tự động mở khóa tạm thời nếu đã hết thời gian khóa
        if ($user->is_locked && $user->locked_until && Carbon::now()->greaterThanOrEqualTo($user->locked_until)) {
            $user->update([
                'is_locked'      => false,
                'locked_until'   => null,
                'login_attempts' => 0,  // Reset số lần đăng nhập sai về 0
                'is_actived'     => true, // Kích hoạt lại trạng thái hoạt động bình thường
            ]);
        }

        // Kiểm tra xem tài khoản có đang bị khóa tạm thời không
        if ($user->is_locked && $user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            $minutesLeft = Carbon::now()->diffInMinutes($user->locked_until) + 1;
            return response()->json([
                'message' => __('api.auth.locked_temp', ['minutes' => $minutesLeft])
            ], 403);
        }

        // 5. Kiểm tra xem tài khoản có bị khóa vĩnh viễn không (is_actived = false và login_attempts >= 11)
        if (!$user->is_actived && $user->login_attempts >= 11) {
            return response()->json([
                'message' => __('api.auth.locked_permanently')
            ], 403);
        }

        // 6. Kiểm tra trạng thái kích hoạt thông thường (Ví dụ: chưa kích hoạt đăng ký)
        if (!$user->is_actived && $user->login_attempts < 5) {
            return response()->json(['message' => __('api.auth.inactive')], 403);
        }

        // 7. KIỂM TRA MẬT KHẨU THỰC TẾ
        if (!Hash::check($request->password, $user->password)) {

            // Tăng số lần đăng nhập sai lên 1 đơn vị
            $user->increment('login_attempts');
            $attempts = $user->login_attempts;

            $lockoutMinutes = 0;

            if ($attempts >= 11) {
                // Từ lần gõ sai thứ 11 trở đi: Khóa vĩnh viễn (is_actived = false)
                $user->update([
                    'is_actived'   => false,
                    'is_locked'    => true,
                    'locked_until' => null // Không có thời gian mở tự động
                ]);
                return response()->json(['message' => __('api.auth.locked_permanently_11')], 403);
            } elseif ($attempts == 10) {
                // Lần thứ 10: Khóa tạm thời 1 giờ (60 phút)
                $lockoutMinutes = 60;
            } elseif ($attempts >= 6) {
                // Từ lần thứ 6 đến lần thứ 9: Khóa tạm thời 2 giờ (120 phút)
                $lockoutMinutes = 120;
            } elseif ($attempts == 5) {
                // Lần thứ 5: Khóa 1 giờ (60 phút) và tạm hủy kích hoạt tài khoản
                $user->update(['is_actived' => false]);
                $lockoutMinutes = 60;
            }

            // Thực thi ghi nhận thời gian khóa tạm thời vào DB nếu nằm trong mốc phạt
            if ($lockoutMinutes > 0) {
                $user->update([
                    'is_locked'    => true,
                    'locked_until' => Carbon::now()->addMinutes($lockoutMinutes)
                ]);
                return response()->json([
                    'message' => __('api.auth.wrong_password_lock_warning', ['minutes' => $lockoutMinutes])
                ], 403);
            }

            // Trả về lỗi sai mật khẩu thông thường (ở các lần thứ 1, 2, 3, 4) kèm thông báo số lần còn lại
            $remainingAttempts = 5 - $attempts;
            return response()->json([
                'message' => __('api.auth.wrong_password_attempts_left', ['attempts' => $remainingAttempts])
            ], 401);
        }

        // 8. ĐĂNG NHẬP THÀNH CÔNG -> Reset sạch lịch sử đăng nhập lỗi
        $user->update([
            'login_attempts' => 0,
            'is_locked'      => false,
            'locked_until'   => null,
            'is_actived'     => true // Khôi phục hoạt động nếu trước đó lỡ bị khóa tạm thời
        ]);

        // 9. Tạo token mới bằng Laravel Sanctum
        $token = $user->createToken($request->input('device_name', 'Game-Client'))->plainTextToken;

        // 10. Trả về LoginResource thành công kèm dữ liệu đăng nhập
        return (new LoginResource($user, $token))
            ->additional(['message' => __('api.auth.success')]);
    }

    /**
     * Đăng xuất hệ thống và thu hồi token
     */
    public function logout(Request $request)
    {
        // Thu hồi/Xóa token hiện tại đang phiên giao dịch
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('api.auth.logout')
        ]);
    }

    /**
     * Lấy thông tin tài khoản cá nhân hiện tại đang đăng nhập
     */
    public function me(Request $request)
    {
        // Lấy thông tin user hiện tại kèm thiết lập game của họ
        $user = $request->user()->load('setting');

        return response()->json([
            'data' => [
                'user' => new UserResource($user)
            ],
            'message' => __('api.user.retrieved')
        ]);
    }

    /**
     * Gửi yêu cầu Quên mật khẩu - Tạo OTP gửi email HTML qua EmailJS
     */
    /**
     * Gửi mã OTP quên mật khẩu qua Email hoặc SMS
     */
    public function forgotPassword(Request $request)
    {
        $identifierField = $request->has('email') ? 'email' : 'phone';

        $validator = Validator::make($request->all(), [
            $identifierField => $identifierField === 'email' ? 'required|email' : 'required|string',
        ], [
            "{$identifierField}.required" => __('api.validation.identifier_required'),
            "{$identifierField}.email"    => __('api.validation.email_invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $value = $request->input($identifierField);

        Log::info('--- [DEBUG FORGOT PASSWORD] ---');
        Log::info('1. Input value gửi lên: ' . $value);

        // Kiểm tra tài khoản tồn tại trong bảng users
        $user = User::where($identifierField, $value)->first();

        if (!$user) {
            Log::warning('2. THẤB BẠI: Không tìm thấy User nào có định danh: ' . $value);
            return response()->json([
                'message' => __('api.auth.account_not_found')
            ], 404);
        }

        Log::info('2. THÀNH CÔNG: Tìm thấy User ID: ' . $user->id . ' | Email trong DB: ' . $user->email);

        // Kiểm tra chắc chắn xem email của User trong DB có thực sự tồn tại không
        if ($identifierField === 'email' && empty($user->email)) {
            Log::error('3. LỖI NGHIÊM TRỌNG: Identifier là email nhưng cột email trong DB của User lại trống rỗng!');
            return response()->json([
                'message' => __('api.auth.mail_failed')
            ], 500);
        }

        // Sinh OTP 6 số
        $otp = random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(15);

        // Lưu OTP đã hash
        DB::table('password_reset_otps')->updateOrInsert(
            ['identifier' => $value],
            [
                'otp'        => Hash::make($otp),
                'expires_at' => $expiresAt,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        // Gửi Email OTP
        if ($identifierField === 'email') {
            try {
                $serviceId  = env('EMAILJS_SERVICE_ID');
                $templateId = env('EMAILJS_TEMPLATE_OTP_ID');
                $publicKey  = env('EMAILJS_PUBLIC_KEY');
                $privateKey = env('EMAILJS_PRIVATE_KEY');

                if (empty($serviceId) || empty($templateId) || empty($publicKey) || empty($privateKey)) {
                    Log::error('4. LỖI: Thiếu cấu hình EMAILJS trong file .env');
                    return response()->json(['message' => __('api.auth.mail_failed')], 500);
                }

                $formattedExpireTime = $expiresAt->format('H:i d/m/Y');

                // Xác định chính xác địa chỉ email đích để gửi
                $recipientEmail = !empty($user->email) ? $user->email : $value;
                Log::info('3. Email đích sẽ gửi sang EmailJS: ' . $recipientEmail);

                $response = Http::timeout(30)
                    ->acceptJson()
                    ->post('https://api.emailjs.com/api/v1.0/email/send', [
                        'service_id'      => $serviceId,
                        'template_id'     => $templateId,
                        'user_id'         => $publicKey,
                        'accessToken'     => $privateKey,
                        'template_params' => [
                            'to_email' => $recipientEmail, // Đảm bảo không bao giờ null nhờ biến fallback ở trên
                            'otp'      => $otp,
                            'time'     => $formattedExpireTime
                        ]
                    ]);

                if (!$response->successful()) {
                    Log::error('4. LỖI TỪ EMAILJS Rest API:', [
                        'status'   => $response->status(),
                        'response' => $response->body(),
                    ]);

                    return response()->json(['message' => __('api.auth.mail_busy')], 500);
                }

                Log::info('4. THÀNH CÔNG: EmailJS đã chấp nhận gửi mail.');

            } catch (\Throwable $e) {
                Log::error('4. EXCEPTION kết nối EmailJS:', [
                    'message' => $e->getMessage()
                ]);

                return response()->json(['message' => __('api.auth.mail_failed')], 500);
            }
        }

        return response()->json([
            'message' => __('api.auth.otp_sent'),
            'dev_otp' => app()->environment('local') ? $otp : null
        ], 200);
    }

    /*
     * Xác thực OTP và đặt lại mật khẩu mới
     */
    public function resetPassword(Request $request)
    {
        $identifierField = $request->has('email') ? 'email' : 'phone';

        $validator = Validator::make($request->all(), [
            $identifierField => $identifierField === 'email' ? 'required|email' : 'required|string',
            'otp'            => 'required|numeric',
            'password'       => 'required|string|min:6|confirmed', // 'password_confirmation' đi kèm
        ], [
            "{$identifierField}.required" => __('api.validation.identifier_required'),
            'otp.required'               => __('api.validation.otp_required'),
            'password.required'          => __('api.validation.password_required'),
            'password.min'               => __('api.validation.password_min'),
            'password.confirmed'         => __('api.validation.password_confirmed'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $value = $request->input($identifierField);

        // 1. Kiểm tra bản ghi OTP trong database
        $resetRecord = DB::table('password_reset_otps')->where('identifier', $value)->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => __('api.auth.otp_invalid')
            ], 400);
        }

        // 2. Kiểm tra xem mã OTP đã hết hạn chưa (Ép kiểu Carbon để tránh lỗi String)
        if (Carbon::now()->greaterThan(Carbon::parse($resetRecord->expires_at))) {
            // Tùy chọn: Xóa luôn OTP hết hạn để dọn dẹp DB
            DB::table('password_reset_otps')->where('identifier', $value)->delete();

            return response()->json([
                'message' => __('api.auth.otp_expired')
            ], 400);
        }

        // 3. So khớp mã OTP bằng cơ chế giải mã băm Hash
        if (!Hash::check($request->otp, $resetRecord->otp)) {
            return response()->json([
                'message' => __('api.auth.otp_incorrect')
            ], 400);
        }

        // 4. Cập nhật mật khẩu mới và mở khóa tài khoản luôn (nếu đang bị khóa)
        $user = User::where($identifierField, $value)->first();
        if ($user) {
            $user->update([
                'password'       => Hash::make($request->password),
                'login_attempts' => 0,
                'is_locked'      => false,
                'locked_until'   => null,
                'is_actived'     => true // Kích hoạt lại khi đổi pass thành công
            ]);

            // 5. Xóa OTP sau khi sử dụng thành công tránh lỗ hổng bảo mật
            DB::table('password_reset_otps')->where('identifier', $value)->delete();

            // 6. Gửi mail thông báo đổi mật khẩu thành công qua EmailJS
            // 6. Gửi mail thông báo đổi mật khẩu thành công qua EmailJS
            if (env('EMAILJS_TEMPLATE_SUCCESS_ID') && !empty($user->email)) {
                try {
                    $formattedNowTime = Carbon::now()->format('H:i d/m/Y');

                    Http::timeout(30)->post('https://api.emailjs.com/api/v1.0/email/send', [
                        'service_id'      => env('EMAILJS_SERVICE_ID'),
                        'template_id'     => env('EMAILJS_TEMPLATE_SUCCESS_ID'),
                        'user_id'         => env('EMAILJS_PUBLIC_KEY'),
                        'accessToken'     => env('EMAILJS_PRIVATE_KEY'),
                        'template_params' => [
                            'user_email' => $user->email, // Bắt chính xác từ DB, không sợ bị rỗng từ request
                            'time'     => $formattedNowTime
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error("Lỗi gửi mail thông báo reset thành công: " . $e->getMessage());
                }
            }

            return response()->json([
                'message' => __('api.auth.reset_success')
            ], 200);
        }

        return response()->json([
            'message' => __('api.auth.failed')
        ], 404);
    }

    /**
     * Thay đổi mật khẩu mới (Khi đang trong trạng thái đăng nhập)
     */
    public function changePassword(Request $request)
    {
        // 1. Kiểm tra đầu vào đổi mật khẩu
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed|different:current_password', // 'password_confirmation' đi kèm
        ], [
            'current_password.required' => __('api.validation.current_password_required'),
            'password.required'         => __('api.validation.password_required'),
            'password.min'              => __('api.validation.password_min'),
            'password.confirmed'        => __('api.validation.password_confirmed'),
            'password.different'        => __('api.validation.password_different'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // 2. Xác minh mật khẩu cũ chính xác
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => __('api.auth.current_password_incorrect')
            ], 400);
        }

        // 3. Cập nhật mật khẩu mới và reset attempts
        $user->update([
            'password'       => Hash::make($request->password),
            'login_attempts' => 0,
            'is_locked'      => false,
            'locked_until'   => null,
        ]);

        return response()->json([
            'message' => __('api.auth.change_password_success')
        ], 200);
    }
}

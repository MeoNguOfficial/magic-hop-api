<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Authentication & User Messages (Tiếng Việt)
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'failed'   => 'Tài khoản hoặc mật khẩu không chính xác.',
        'banned'   => 'Tài khoản của bạn đã bị khóa khỏi hệ thống.',
        'inactive' => 'Tài khoản chưa được kích hoạt.',
        'success'  => 'Đăng nhập thành công!',
        'logout'   => 'Đăng xuất thành công, mã xác thực token đã được hủy.',
        'mail_busy' => 'Hệ thống gửi mail đang bận, vui lòng thử lại sau.',
        'mail_failed' => 'Không thể kết nối đến tổng đài gửi mã OTP, thử lại sau.',
        'forbidden'   => 'Bạn không có quyền thực hiện hành động này.',
        'current_password_incorrect' => 'Mật khẩu hiện tại không chính xác.',
        'change_password_success' => 'Thay đổi mật khẩu tài khoản thành công.',
        'locked_temp' => 'Tài khoản của bạn tạm thời bị khóa. Vui lòng thử lại sau :minutes phút.',
        'locked_permanently' => 'Tài khoản của bạn đã bị khóa vĩnh viễn do nhập sai quá nhiều lần. Vui lòng liên hệ hỗ trợ.',
        'locked_permanently_11' => 'Tài khoản của bạn đã bị khóa vĩnh viễn do nhập sai 11 lần.',
        'wrong_password_lock_warning' => 'Sai mật khẩu quá nhiều lần. Tài khoản bị khóa tạm thời trong :minutes phút.',
        'wrong_password_attempts_left' => 'Mật khẩu không chính xác. Bạn còn :attempts lần thử trước khi tài khoản bị khóa tạm thời.',
        'account_not_found' => 'Không tìm thấy tài khoản liên kết với thông tin này.',
        'otp_sent' => 'Mã xác thực OTP đã được gửi đi thành công.',
        'otp_invalid' => 'Yêu cầu xác thực OTP không hợp lệ.',
        'otp_expired' => 'Mã OTP đã hết hạn sử dụng.',
        'otp_incorrect' => 'Mã OTP không chính xác.',
        'reset_success' => 'Mật khẩu của bạn đã được cập nhật lại thành công.',
        'register_success' => 'Đăng ký tài khoản thành công!',
    ],
    'user' => [
        'created'   => 'Đăng ký tài khoản mới thành công.',
        'updated'   => 'Cập nhật thông tin cá nhân thành công.',
        'not_found' => 'Không tìm thấy người dùng này trên hệ thống.',
        'retrieved' => 'Lấy thông tin tài khoản thành công!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Settings & Beatmaps Messages
    |--------------------------------------------------------------------------
    */
    'setting' => [
        'updated'   => 'Cập nhật cấu hình cài đặt game thành công.',
        'not_found' => 'Không tìm thấy dữ liệu cài đặt của người dùng này.',
    ],
    'beatmap' => [
        'created'        => 'Tạo bài nhạc mới thành công.',
        'updated'        => 'Cập nhật thông tin bài nhạc thành công.',
        'deleted'        => 'Xóa bài nhạc khỏi hệ thống thành công.',
        'not_found'      => 'Không tìm thấy bài nhạc yêu cầu.',
        'import_success' => 'Nhập dữ liệu cấu trúc tệp JSON (Import) bài nhạc thành công.',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Scores Messages
    |--------------------------------------------------------------------------
    */
    'score' => [
        'saved'     => 'Điểm số của bạn đã được ghi nhận thành công!',
        'not_found' => 'Không tìm thấy dữ liệu điểm số yêu cầu.',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Input Form Validation Messages
    |--------------------------------------------------------------------------
    */
    'validation' => [
        // Auth & Users
        'username_required' => 'Vui lòng điền tên tài khoản.',
        'password_required' => 'Vui lòng nhập mật khẩu đăng nhập.',
        'email_required'    => 'Vui lòng cung cấp địa chỉ Email.',
        'email_unique'      => 'Địa chỉ Email này đã được người khác sử dụng.',
        'identifier_required' => 'Vui lòng nhập Email hoặc Số điện thoại.',
        'email_invalid' => 'Định dạng Email không chính xác.',
        'otp_required' => 'Vui lòng nhập mã OTP.',
        'password_min' => 'Mật khẩu phải tối thiểu 6 ký tự.',
        'password_confirmed' => 'Xác nhận mật khẩu không khớp.',
        'phone_unique' => 'Số điện thoại đã tồn tại trong hệ thống.',
        'current_password_required' => 'Vui lòng nhập mật khẩu hiện tại.',
        'password_different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',

        // Beatmaps
        'name_required'     => 'Tên bài nhạc không được để trống.',
        'url_required'      => 'Vui lòng cung cấp liên kết tải tệp âm nhạc.',
        'url_invalid'       => 'Liên kết âm nhạc (.mp3) không đúng định dạng URL hợp lệ.',
        'beats_required'    => 'Dữ liệu các nốt nhạc (beats) bắt buộc phải có.',
        'beats_array'       => 'Dữ liệu nốt nhạc gửi lên cấu trúc phải là một mảng dữ liệu (Array).',
        'json_invalid'      => 'Chuỗi hoặc định dạng ngày tháng trong tệp JSON gửi lên không hợp lệ.',

        // Scores
        'beatmap_id_required' => 'Vui lòng cung cấp mã ID của bài nhạc.',
        'beatmap_id_exists'   => 'Bài nhạc được chọn không tồn tại trên hệ thống.',
        'score_required'      => 'Điểm số đạt được không được để trống.',
        'score_integer'       => 'Điểm số màn chơi bắt buộc phải là một số nguyên dương.',
    ]
];

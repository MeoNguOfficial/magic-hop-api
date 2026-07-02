<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // -------------------------------------------------------------
        // TÀI KHOẢN 1: Quản trị viên tối cao (Admin)
        // -------------------------------------------------------------
        $admin = User::create([
            'id'         => (string) Str::ulid(), // Sinh chuỗi khóa chính ULID ngẫu nhiên
            'username'   => 'admin',
            'realname'   => 'Quản Trị Viên',
            'password'   => Hash::make('MeoTN@'), // Mật khẩu mã hóa bảo mật
            'email'      => 'admin@magichop.com',
            'phone'      => '0987654321',
            'is_admin'   => true,   // Quyền kiểm soát hệ thống, import JSON
            'is_banned'  => false,
            'is_actived' => true,   // Đã kích hoạt hoạt động
        ]);
        // Tạo sẵn cấu hình game mặc định đi kèm cho Admin
        $admin->setting()->create();


        // -------------------------------------------------------------
        // TÀI KHOẢN 2: Người chơi thử nghiệm (Tester / Normal User)
        // -------------------------------------------------------------
        $player = User::create([
            'id'         => (string) Str::ulid(),
            'username'   => 'playerone',
            'realname'   => 'Nguyễn Văn Chơi Game',
            'password'   => Hash::make('111111'),
            'email'      => 'playerone@gmail.com',
            'phone'      => '0912345678',
            'is_admin'   => false,  // Tài khoản người chơi thông thường
            'is_banned'  => false,
            'is_actived' => true,
        ]);
        // Tạo sẵn cấu hình game mặc định đi kèm cho Player
        $player->setting()->create();


        // -------------------------------------------------------------
        // TỰ ĐỘNG SINH THÊM 10 TÀI KHOẢN KHÁCH ĐỂ LÀM DỮ LIỆU BẢNG XẾP HẠNG
        // -------------------------------------------------------------
        for ($i = 1; $i <= 10; $i++) {
            $bot = User::create([
                'id'         => (string) Str::ulid(),
                'username'   => "racer_bot_$i",
                'realname'   => "Cao Thủ Khách Mời $i",
                'password'   => Hash::make('111111'),
                'email'      => "bot_racer_$i@magichop.com",
                'is_admin'   => false,
                'is_banned'  => false,
                'is_actived' => true,
            ]);
            // Mỗi người chơi ảo cũng cần có bản ghi cài đặt game riêng
            $bot->setting()->create();
        }
    }
}

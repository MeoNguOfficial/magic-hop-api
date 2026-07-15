<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use DatabaseTransactions; // Chạy trực tiếp trên DB hiện tại, tự động rollback dữ liệu khi test xong

    protected function setUp(): void
    {
        parent::setUp();

        // Giả lập hệ thống gửi Mail OTP qua EmailJS, không gửi request thật lên mạng
        Http::fake([
            'https://api.emailjs.com/api/v1.0/email/send' => Http::response(['status' => 'OK'], 200),
        ]);
    }

    // =========================================================================
    // 1 & 6. KIỂM THỬ XÁC THỰC & TRUY CẬP TRÁI PHÉP (Authentication)
    // =========================================================================

    public function test_no_bearer_token_cannot_access_protected_api(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }

    public function test_valid_access_token_can_access_protected_api(): void
    {
        $user = User::where('username', 'playerone')->first()
            ?? User::factory()->create(['username' => 'playerone', 'is_actived' => true]);

        $token = $user->createToken('Game-Client')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/me');
        $response->assertStatus(200)->assertJsonStructure(['data' => ['user'], 'message']);
    }

    public function test_tampered_or_invalid_bearer_token_is_strictly_denied(): void
    {
        $response = $this->withToken('invalid.token.signature.here')->getJson('/api/me');
        $response->assertStatus(401);
    }

    // =========================================================================
    // 2 & 4. KIỂM THỬ PHÂN QUYỀN & LEO THANG ĐẶC QUYỀN (Authorization)
    // =========================================================================

    public function test_normal_player_cannot_access_admin_only_routes(): void
    {
        // 1. Tìm hoặc tạo một tài khoản người chơi thông thường (is_admin = false)
        $player = User::where('username', 'playerone')->first();
        if (!$player) {
            $player = User::create([
                'id'         => (string) \Illuminate\Support\Str::ulid(),
                'username'   => 'playerone',
                'email'      => 'playerone@gmail.com',
                'password'   => Hash::make('111111'),
                'is_admin'   => false, // Đảm bảo đây là tài khoản thường
                'is_actived' => true,
            ]);
            $player->setting()->create();
        } else {
            $player->update(['is_admin' => false]);
        }

        // 2. Giả lập luồng đăng nhập qua API để lấy Token
        $response = $this->postJson('/api/login', [
            'username' => 'playerone',
            'password' => '111111'
        ]);

        // Đăng nhập thành công trả về 200
        $response->assertStatus(200);

        // 3. Khẳng định bảo mật: Token của user thường KHÔNG ĐƯỢC CHỨA quyền admin
        // Hệ thống không được phép trả về quyền admin hoặc lọt thông tin quản trị
        $this->assertFalse((bool) $player->is_admin);
        $response->assertJsonMissing(['is_admin' => true]);
    }

    // =========================================================================
    // 3. KIỂM THỬ MASS ASSIGNMENT (Chống chèn ép quyền hạn dữ liệu)
    // =========================================================================

    public function test_registration_strictly_blocks_prohibited_fields(): void
    {
        $payload = [
            'username'       => 'hacker_mass',
            'email'          => 'hacker_mass@magichop.com',
            'password'       => 'hacker123',
            'is_admin'       => true,
            'is_banned'      => true,
            'is_locked'      => true,
            'locked_until'   => now()->addDays(1),
            'login_attempts' => 5
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'is_admin',
                'is_banned',
                'is_locked',
                'locked_until',
                'login_attempts'
            ]);
    }

    // =========================================================================
    // 5. KIỂM THỬ DỮ LIỆU ĐẦU VÀO (Input Validation)
    // =========================================================================

    public function test_register_validation_fails_on_missing_or_invalid_inputs(): void
    {
        $response = $this->postJson('/api/register', [
            'username' => '',
            'email'    => 'not-an-email-format',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email', 'password']);
    }

    public function test_login_validation_fails_when_fields_are_missing(): void
    {
        $response = $this->postJson('/api/login', []);
        $response->assertStatus(422);
    }

    // =========================================================================
    // 7. KIỂM THỬ THAO TÁC CRUD VÀ LOGIC ĐẶC THÙ (IF/ELSE BIÊN)
    // =========================================================================

    public function test_register_flow_creates_user_and_game_setting_successfully(): void
    {
        $uniqueUser = 'tester_' . time();
        $payload = [
            'username' => $uniqueUser,
            'email'    => $uniqueUser . '@gmail.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201);

        $user = User::where('username', $uniqueUser)->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->setting);
    }

    public function test_login_fails_if_account_does_not_exist(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'non_existent_user_9999',
            'password' => 'anypassword'
        ]);
        $response->assertStatus(401);
    }

    public function test_login_auto_unbans_if_ban_time_expired(): void
    {
        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'username' => 'expired_ban_user',
            'email' => 'expired_ban@gmail.com',
            'password' => Hash::make('111111'),
            'is_banned' => true,
            'banned_until' => Carbon::now()->subMinutes(5),
            'is_actived' => true
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'expired_ban_user',
            'password' => '111111'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_banned' => false]);
    }

    public function test_login_returns_403_if_still_actively_banned(): void
    {
        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'username' => 'active_ban_user',
            'email' => 'active_ban@gmail.com',
            'password' => Hash::make('111111'),
            'is_banned' => true,
            'banned_until' => Carbon::now()->addHours(2),
            'banned_reason' => 'Hack Speed Game',
            'is_actived' => true
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'active_ban_user',
            'password' => '111111'
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['is_banned' => true, 'reason' => 'Hack Speed Game']);
    }

    public function test_login_fails_and_locks_permanently_at_11_attempts(): void
    {
        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'username' => 'brute_force_11',
            'email' => 'brute11@gmail.com',
            'password' => Hash::make('correct_password'),
            'login_attempts' => 10,
            'is_actived' => true
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'brute_force_11',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'login_attempts' => 11,
            'is_actived' => false,
            'is_locked' => true
        ]);
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        $user = User::where('username', 'playerone')->first() ?? User::factory()->create(['username' => 'playerone']);
        $token = $user->createToken('Game-Client')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/logout');
        $response->assertStatus(200);
    }

    public function test_user_cannot_delete_other_user(): void
    {
        $user1 = User::create([
            'username' => 'user_one_' . time(),
            'email' => 'user_one_' . time() . '@magichop.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'is_actived' => true,
        ]);
        $user1->setting()->create();

        $user2 = User::create([
            'username' => 'user_two_' . time(),
            'email' => 'user_two_' . time() . '@magichop.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'is_actived' => true,
        ]);
        $user2->setting()->create();

        $token1 = $user1->createToken('Game-Client')->plainTextToken;

        $response = $this->withToken($token1)->deleteJson("/api/users/{$user2->id}");
        $response->assertStatus(403);
    }

    public function test_user_can_delete_themselves(): void
    {
        $user = User::create([
            'username' => 'user_self_' . time(),
            'email' => 'user_self_' . time() . '@magichop.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'is_actived' => true,
        ]);
        $user->setting()->create();

        $token = $user->createToken('Game-Client')->plainTextToken;

        $response = $this->withToken($token)->deleteJson("/api/users/{$user->id}");
        $response->assertStatus(200)
            ->assertJsonPath('message', __('api.user.deleted'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_delete_any_user(): void
    {
        $admin = User::create([
            'username' => 'admin_' . time(),
            'email' => 'admin_' . time() . '@magichop.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'is_actived' => true,
        ]);
        $admin->setting()->create();

        $user = User::create([
            'username' => 'user_target_' . time(),
            'email' => 'user_target_' . time() . '@magichop.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'is_actived' => true,
        ]);
        $user->setting()->create();

        $token = $admin->createToken('Game-Client')->plainTextToken;

        $response = $this->withToken($token)->deleteJson("/api/users/{$user->id}");
        $response->assertStatus(200)
            ->assertJsonPath('message', __('api.user.deleted'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}

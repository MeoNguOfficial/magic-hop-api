<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;

class UserSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUser($isAdmin = false)
    {
        $user = new User();
        $user->forceFill([
            'id' => (string) Str::ulid(),
            'username' => 'testuser_' . Str::random(5),
            'email' => Str::random(5) . '@test.com',
            'password' => 'password123',
            'is_admin' => $isAdmin,
            'is_actived' => true,
        ]);
        $user->save();
        $user->setting()->create();
        return $user;
    }

    public function test_user_can_update_own_settings()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson("/api/user-settings/{$user->id}", [
            'game_volume' => 0.5,
            'is_game_muted' => true
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'game_volume' => 0.5,
            'is_game_muted' => 1
        ]);
    }

    public function test_user_cannot_update_others_settings_idor()
    {
        $hacker = $this->createUser();
        $victim = $this->createUser();

        $response = $this->actingAs($hacker)->putJson("/api/user-settings/{$victim->id}", [
            'game_volume' => 0.9
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_settings()
    {
        $admin = $this->createUser(true);
        $victim = $this->createUser();

        $response = $this->actingAs($admin)->putJson("/api/user-settings/{$victim->id}", [
            'game_volume' => 0.7
        ]);

        $response->assertStatus(200);
    }

    public function test_db_connection_error_returns_custom_json()
    {
        // Gây ra lỗi DB bằng cách thiết lập host sai và cổng sai để throw PDOException/QueryException
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.host' => '127.0.0.99']);
        config(['database.connections.mysql.port' => '9999']);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::purge('sqlite');

        // Gọi 1 API công khai có tương tác với DB (giả sử có Route get /api/beatmaps)
        $response = $this->getJson('/api/beatmaps'); 

        // Kiểm tra xem mã lỗi có đúng 500 và trả về cấu trúc JSON custom hay không
        $response->assertStatus(500);
        $response->assertJson([
            'error_code' => 'DATABASE_CONNECTION_ERROR'
        ]);
    }
}

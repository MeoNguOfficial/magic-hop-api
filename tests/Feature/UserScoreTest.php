<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Beatmap;
use App\Models\UserScore;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserScoreTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $token;
    protected $beatmap;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'username' => 'score_tester_' . uniqid(),
            'email' => 'tester_' . uniqid() . '@magichop.com',
            'password' => Hash::make('password123'),
            'is_actived' => true,
        ]);
        $this->user->setting()->create();

        // Generate token
        $this->token = $this->user->createToken('TestToken')->plainTextToken;

        // Create a test beatmap
        $this->beatmap = Beatmap::create([
            'name' => 'Test Beatmap',
            'artist' => 'Test Artist',
            'url' => 'https://example.com/song.mp3',
            'beats' => ['beat1', 'beat2'],
        ]);
    }

    public function test_can_store_initial_scores()
    {
        $response = $this->withToken($this->token)->postJson('/api/scores', [
            'beatmap_id' => $this->beatmap->id,
            'score' => 100,
            'hard_mode_score' => 50,
            'is_normal_mode_passed' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.score', 100);
        $response->assertJsonPath('data.hard_mode_score', 50);
        $response->assertJsonPath('data.is_normal_mode_passed', true);

        $this->assertDatabaseHas('user_scores', [
            'user_id' => $this->user->id,
            'beatmap_id' => $this->beatmap->id,
            'score' => 100,
            'hard_mode_score' => 50,
            'is_normal_mode_passed' => true,
        ]);
    }

    public function test_store_updates_only_improved_scores()
    {
        // First, create the initial score
        $scoreObj = UserScore::create([
            'user_id' => $this->user->id,
            'beatmap_id' => $this->beatmap->id,
            'score' => 100,
            'hard_mode_score' => 50,
            'is_normal_mode_passed' => false,
        ]);

        // Scenario 1: Send worse scores
        $response = $this->withToken($this->token)->postJson('/api/scores', [
            'beatmap_id' => $this->beatmap->id,
            'score' => 80,
            'hard_mode_score' => 40,
            'is_normal_mode_passed' => true,
        ]);

        $response->assertStatus(200);
        // Scores should remain unchanged
        $response->assertJsonPath('data.score', 100);
        $response->assertJsonPath('data.hard_mode_score', 50);
        $response->assertJsonPath('data.is_normal_mode_passed', false);

        // Scenario 2: Send better normal score, worse/null hard score
        $response = $this->withToken($this->token)->postJson('/api/scores', [
            'beatmap_id' => $this->beatmap->id,
            'score' => 120,
            'hard_mode_score' => 30,
            'is_normal_mode_passed' => true,
        ]);

        $response->assertStatus(200);
        // normal score and pass status should update; hard_mode_score should stay 50
        $response->assertJsonPath('data.score', 120);
        $response->assertJsonPath('data.hard_mode_score', 50);
        $response->assertJsonPath('data.is_normal_mode_passed', true);

        // Scenario 3: Send worse normal score, better hard score
        $response = $this->withToken($this->token)->postJson('/api/scores', [
            'beatmap_id' => $this->beatmap->id,
            'score' => 90,
            'hard_mode_score' => 70,
            'is_normal_mode_passed' => false,
        ]);

        $response->assertStatus(200);
        // hard_mode_score should update to 70; normal score should remain 120
        $response->assertJsonPath('data.score', 120);
        $response->assertJsonPath('data.hard_mode_score', 70);
        $response->assertJsonPath('data.is_normal_mode_passed', true);
    }

    public function test_validation_fails_for_invalid_hard_mode_score()
    {
        $response = $this->withToken($this->token)->postJson('/api/scores', [
            'beatmap_id' => $this->beatmap->id,
            'score' => 100,
            'hard_mode_score' => 'invalid-integer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['hard_mode_score']);
    }

    public function test_leaderboard_sorting_modes()
    {
        // Create another user
        $user2 = User::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'username' => 'user_two_' . uniqid(),
            'email' => 'user_two_' . uniqid() . '@magichop.com',
            'password' => Hash::make('password123'),
            'is_actived' => true,
        ]);
        $user2->setting()->create();

        // User 1 has normal: 100, hard: 50
        UserScore::create([
            'user_id' => $this->user->id,
            'beatmap_id' => $this->beatmap->id,
            'score' => 100,
            'hard_mode_score' => 50,
            'is_normal_mode_passed' => true,
        ]);

        // User 2 has normal: 50, hard: 150
        UserScore::create([
            'user_id' => $user2->id,
            'beatmap_id' => $this->beatmap->id,
            'score' => 50,
            'hard_mode_score' => 150,
            'is_normal_mode_passed' => true,
        ]);

        // Test normal mode leaderboard (default) -> User 1 should be first
        $response = $this->withToken($this->token)->getJson("/api/beatmaps/{$this->beatmap->id}/leaderboard");
        $response->assertStatus(200);
        $this->assertEquals($this->user->id, $response->json('data.0.user.id'));
        $this->assertEquals($user2->id, $response->json('data.1.user.id'));

        // Test hard mode leaderboard -> User 2 should be first
        $response = $this->withToken($this->token)->getJson("/api/beatmaps/{$this->beatmap->id}/leaderboard?mode=hard");
        $response->assertStatus(200);
        $this->assertEquals($user2->id, $response->json('data.0.user.id'));
        $this->assertEquals($this->user->id, $response->json('data.1.user.id'));
    }
}

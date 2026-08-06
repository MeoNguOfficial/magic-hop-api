<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserScore extends Model
{
    use HasFactory;

    protected $table = 'user_scores';

    protected $fillable = [
        'user_id',
        'beatmap_id',
        'easy_mode_score',
        'is_easy_mode_passed',
        'score',
        'is_normal_mode_passed',
        'hard_mode_score',
        'is_hard_mode_passed',
        'asian_mode_score',
        'is_asian_mode_passed'
    ];

    protected $casts = [
        'beatmap_id'            => 'integer',
        'easy_mode_score'       => 'integer',
        'is_easy_mode_passed'   => 'boolean',
        'score'                 => 'integer',
        'is_normal_mode_passed' => 'boolean',
        'hard_mode_score'       => 'integer',
        'is_hard_mode_passed'   => 'boolean',
        'asian_mode_score'      => 'integer',
        'is_asian_mode_passed'  => 'boolean',
    ];

    /**
     * Liên kết ngược tới bảng Users (Khóa ngoại user_id dạng string ULID)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Liên kết ngược tới bảng Beatmaps (Khóa ngoại beatmap_id dạng BigInteger)
     */
    public function beatmap(): BelongsTo
    {
        return $this->belongsTo(Beatmap::class, 'beatmap_id', 'id');
    }
}

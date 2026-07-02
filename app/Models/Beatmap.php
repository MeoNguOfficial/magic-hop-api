<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beatmap extends Model
{
    use HasFactory;

    protected $table = 'beatmaps';

    protected $fillable = [
        'name',
        'artist',
        'speed',
        'genre',
        'bpm',
        'copyright_status',
        'no_fake_block',
        'url',
        'warning_alert',
        'day_show',
        'day_hide',
        'is_available',
        'beats',
    ];

    protected $casts = [
        'speed' => 'integer',
        'bpm' => 'integer',
        'no_fake_block' => 'boolean',
        'is_available' => 'boolean',
        'day_show' => 'date',
        'day_hide' => 'date',
        'beats' => 'array', // Tự động hóa Cast JSON string sang PHP Array
    ];
}

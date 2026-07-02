<?php

namespace App\Http\Controllers;

use App\Models\Beatmap;
use App\Http\Resources\BeatmapResource;
use Illuminate\Http\Request;

class PublicBeatmapController extends Controller
{
    /**
     * Lấy danh sách các màn chơi đang khả dụng (Public)
     */
    public function index()
    {
        $now = now()->toDateString();

        // Chỉ lấy các map được bật kích hoạt công khai và nằm trong khung thời gian hiển thị
        $beatmaps = Beatmap::where('is_available', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('day_show')->orWhere('day_show', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('day_hide')->orWhere('day_hide', '>=', $now);
            })
            ->orderBy('id', 'desc')
            ->cursorPaginate(10);

        return BeatmapResource::collection($beatmaps);
    }

    /**
     * Lấy cấu trúc chi tiết nốt nhạc/bản dịch của một màn chơi (Public)
     */
    public function show($id)
    {
        $beatmap = Beatmap::find($id);

        if (!$beatmap) {
            return response()->json([
                'message' => __('api.beatmap.not_found')
            ], 404);
        }

        return new BeatmapResource($beatmap);
    }
}

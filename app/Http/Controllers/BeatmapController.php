<?php

namespace App\Http\Controllers;

use App\Models\Beatmap;
use App\Http\Resources\BeatmapResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BeatmapController extends Controller
{
    /**
     * Lấy danh sách Beatmaps (Phân luồng Public / Admin) bằng cơ chế Cursor Lazy Loading.
     * Cơ chế này hỗ trợ cực tốt cho việc tối ưu hóa bộ nhớ client (unload/virtual scroll)
     * và tăng tốc độ truy vấn ở phía Database khi dữ liệu lớn.
     */
    /**
     * Lấy danh sách Beatmaps (Phân luồng Public / Admin) bằng cơ chế Cursor Lazy Loading.
     * Hỗ trợ tìm kiếm theo tên bài hát (name) hoặc tác giả (artist).
     */
    public function index(Request $request)
    {
        $query = Beatmap::query()->orderBy('id', 'desc');
        $perPage = 10; // Mặc định cho người chơi (Public)

        // --- Bắt đầu bổ sung: Tìm kiếm theo từ khóa ---
        if ($request->filled('search')) {
            $keyword = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', $keyword)
                  ->orWhere('artist', 'like', $keyword);
            });
        }
        // --- Kết thúc bổ sung ---

        // Nếu KHÔNG PHẢI admin (Luồng Public dành cho người chơi)
        if ($request->query('mode') !== 'admin') {
            $now = now()->toDateString();

            $query->where('is_available', true) // Chỉ lấy map đang bật
                ->where(function ($q) use ($now) {
                    // Chỉ lấy nếu trống ngày hiển thị HOẶC đã đến ngày hiển thị
                    $q->whereNull('day_show')->orWhere('day_show', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    // Chỉ lấy nếu trống ngày ẩn HOẶC chưa tới ngày ẩn
                    $q->whereNull('day_hide')->orWhere('day_hide', '>=', $now);
                });
        } else {
            $perPage = 20; // Nếu là admin, đổi số lượng item hiển thị mỗi trang
        }

        // --- Bắt đầu áp dụng Cursor Lazy Loading ---
        // Lấy con trỏ (ID của phần tử cuối cùng ở trang trước) gửi từ Client lên
        $cursor = $request->query('cursor');

        if ($cursor && is_numeric($cursor)) {
            // Vì danh sách sắp xếp theo ID giảm dần (id desc),
            // các phần tử tiếp theo sẽ có ID nhỏ hơn cursor hiện tại.
            $query->where('id', '<', $cursor);
        }

        // Lấy dư ra 1 bản ghi so với $perPage để kiểm tra xem còn dữ liệu tiếp theo hay không (has_more)
        $beatmaps = $query->limit($perPage + 1)->get();

        $hasMore = $beatmaps->count() > $perPage;

        if ($hasMore) {
            // Loại bỏ bản ghi thừa thứ $perPage + 1 dùng để check "has_more" ra khỏi tập dữ liệu thực tế
            $beatmaps->pop();
        }

        // Lấy ID của phần tử cuối cùng trong trang hiện tại để làm con trỏ (cursor) tiếp theo
        $nextCursor = $beatmaps->last()?->id;

        // Trả về dữ liệu kết hợp với Meta Cursor để Frontend quản lý tải/hủy tải (load/unload)
        return BeatmapResource::collection($beatmaps)->additional([
            'meta' => [
                'next_cursor' => $hasMore ? $nextCursor : null,
                'has_more'    => $hasMore,
                'per_page'    => $perPage,
                'count'       => $beatmaps->count(),
            ]
        ]);
    }

    /**
     * Lấy chi tiết một bài nhạc cụ thể
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

    /**
     * Thêm mới một Beatmap (Create)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'url'          => 'required|url',
            'beats'        => 'required|array',
            'artist'       => 'sometimes|string|max:255',
            'speed'        => 'nullable|integer',
            'genre'        => 'nullable|string|max:100',
            'bpm'          => 'nullable|integer',
            'day_show'     => 'nullable|date',
            'day_hide'     => 'nullable|date',
            'is_available' => 'sometimes|boolean',
        ], [
            'name.required'  => __('api.validation.name_required'),
            'url.required'   => __('api.validation.url_required'),
            'url.url'        => __('api.validation.url_invalid'),
            'beats.required' => __('api.validation.beats_required'),
            'beats.array'    => __('api.validation.beats_array'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if (isset($data['speed']) && (int) $data['speed'] === 0) {
            $data['speed'] = 18; // Tốc độ mặc định của game
        }

        $beatmap = Beatmap::create($data);

        return (new BeatmapResource($beatmap))
            ->additional(['message' => __('api.beatmap.created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Hỗ trợ chức năng Import cấu trúc JSON bài nhạc trực tiếp
     */
    public function createWithJson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'artist'           => 'sometimes|string|max:255',
            'speed'            => 'nullable|integer',
            'genre'            => 'nullable|string|max:100',
            'bpm'              => 'nullable|integer',
            'copyright_status' => 'nullable|string|max:100',
            'no_fake_block'    => 'sometimes|boolean',
            'url'              => 'required|url',
            'warning_alert'    => 'nullable|string',
            'day_show'         => 'nullable|date',
            'day_hide'         => 'nullable|date',
            'is_available'     => 'sometimes|boolean',
            'beats'            => 'required|array',
        ], [
            'name.required'  => __('api.validation.name_required'),
            'url.required'   => __('api.validation.url_required'),
            'url.url'        => __('api.validation.url_invalid'),
            'beats.required' => __('api.validation.beats_required'),
            'beats.array'    => __('api.validation.beats_array'),
            'day_show.date'  => __('api.validation.json_invalid'),
            'day_hide.date'  => __('api.validation.json_invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'name',
            'artist',
            'speed',
            'genre',
            'bpm',
            'copyright_status',
            'no_fake_block',
            'url',
            'warning_alert',
            'is_available',
            'beats'
        ]);

        $data['day_show'] = $request->filled('day_show') ? date('Y-m-d', strtotime($request->day_show)) : null;
        $data['day_hide'] = $request->filled('day_hide') ? date('Y-m-d', strtotime($request->day_hide)) : null;

        if (isset($data['speed']) && (int) $data['speed'] === 0) {
            $data['speed'] = 15;
        }

        $beatmap = Beatmap::create($data);

        return (new BeatmapResource($beatmap))
            ->additional(['message' => __('api.beatmap.import_success')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Cập nhật thông tin bài nhạc (Update)
     */
    public function update(Request $request, $id)
    {
        $beatmap = Beatmap::find($id);

        if (!$beatmap) {
            return response()->json([
                'message' => __('api.beatmap.not_found')
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|string|max:255',
            'url'          => 'sometimes|required|url',
            'beats'        => 'sometimes|required|array',
            'artist'       => 'sometimes|string|max:255',
            'speed'        => 'nullable|integer',
            'genre'        => 'nullable|string|max:100',
            'bpm'          => 'nullable|integer',
            'day_show'     => 'nullable|date',
            'day_hide'     => 'nullable|date',
            'is_available' => 'sometimes|boolean',
        ], [
            'name.required'  => __('api.validation.name_required'),
            'url.required'   => __('api.validation.url_required'),
            'url.url'        => __('api.validation.url_invalid'),
            'beats.required' => __('api.validation.beats_required'),
            'beats.array'    => __('api.validation.beats_array'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if (isset($data['speed']) && (int) $data['speed'] === 0) {
            $data['speed'] = 15;
        }

        $beatmap->update($data);

        return (new BeatmapResource($beatmap))
            ->additional(['message' => __('api.beatmap.updated')]);
    }

    /**
     * Xóa bài nhạc khỏi hệ thống (Delete)
     */
    public function destroy($id)
    {
        $beatmap = Beatmap::find($id);

        if (!$beatmap) {
            return response()->json([
                'message' => __('api.beatmap.not_found')
            ], 404);
        }

        $beatmap->delete();

        return response()->json([
            'message' => __('api.beatmap.deleted')
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\UserScore;
use App\Http\Resources\UserScoreResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserScoreController extends Controller
{
    /**
     * Lấy danh sách lịch sử điểm số (Có hỗ trợ lọc theo beatmap_id hoặc user_id)
     * Sử dụng cơ chế Cursor Lazy Loading để tối ưu hóa hiệu năng và bộ nhớ client.
     */
    public function index(Request $request)
    {
        // Nạp sẵn quan hệ user và beatmap để tránh lỗi N+1 Query
        $query = UserScore::with(['user', 'beatmap'])->orderBy('id', 'desc');
        $perPage = min((int) $request->query('limit', 15), 1000); // Số lượng bản ghi mỗi trang

        // Lọc theo beatmap_id nếu có truyền lên
        if ($request->filled('beatmap_id')) {
            $query->where('beatmap_id', $request->query('beatmap_id'));
        }

        // Lọc theo user_id nếu có truyền lên
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        // --- Bắt đầu áp dụng Cursor Lazy Loading ---
        $cursor = $request->query('cursor');

        if ($cursor && is_numeric($cursor)) {
            $query->where('id', '<', $cursor);
        }

        $scores = $query->limit($perPage + 1)->get();

        $hasMore = $scores->count() > $perPage;

        if ($hasMore) {
            $scores->pop();
        }

        $nextCursor = $scores->last()?->id;

        return UserScoreResource::collection($scores)->additional([
            'meta' => [
                'next_cursor' => $hasMore ? $nextCursor : null,
                'has_more'    => $hasMore,
                'per_page'    => $perPage,
                'count'       => $scores->count(),
            ]
        ]);
    }

    /**
     * Lưu hoặc cập nhật điểm số (Chỉ lưu nếu đạt kỷ lục mới - New Personal Best)
     */
    public function store(Request $request)
    {
        // 1. Validate dữ liệu đầu vào gửi lên từ Game Client
        $validator = Validator::make($request->all(), [
            'beatmap_id'            => 'required|exists:beatmaps,id',
            'score'                 => 'nullable|integer|min:0',
            'beat'                  => 'nullable|array',
            'beats'                 => 'nullable|array',
            'round_endless'         => 'nullable|integer|min:1',
            'endless_round'         => 'nullable|integer|min:1',
            'round'                 => 'nullable|integer|min:1',
            'round_count'           => 'nullable|integer|min:1',
            'endless_count'         => 'nullable|integer|min:1',
            'hard_mode_score'       => 'nullable|integer|min:0',
            'is_normal_mode_passed' => 'nullable|boolean',
        ], [
            'beatmap_id.required'            => __('api.validation.beatmap_id_required'),
            'beatmap_id.exists'              => __('api.validation.beatmap_id_exists'),
            'score.integer'                  => __('api.validation.score_integer'),
            'beat.array'                     => __('api.validation.beat_array'),
            'beats.array'                    => __('api.validation.beat_array'),
            'round_endless.integer'          => __('api.validation.round_endless_integer'),
            'endless_round.integer'          => __('api.validation.round_endless_integer'),
            'round.integer'                  => __('api.validation.round_endless_integer'),
            'round_count.integer'            => __('api.validation.round_endless_integer'),
            'endless_count.integer'          => __('api.validation.round_endless_integer'),
            'hard_mode_score.integer'        => __('api.validation.hard_mode_score_integer'),
            'is_normal_mode_passed.boolean'  => __('api.validation.is_normal_mode_passed_boolean'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Bắt buộc phải truyền ít nhất 1 trong 2: score hoặc mảng beat
        if (!$request->has('score') && !$request->has('beat') && !$request->has('beats')) {
            return response()->json([
                'errors' => [
                    'score' => [__('api.validation.score_required')]
                ]
            ], 422);
        }

        $userId = $request->user()->id;
        $beatmapId = $request->beatmap_id;
        $newHardScore = $request->hard_mode_score;
        $isNormalModePassed = (bool) ($request->is_normal_mode_passed ?? false);

        // 2. Tìm điểm số kỷ lục hiện tại của User trên Beatmap này (nếu có)
        $existingScore = UserScore::where('user_id', $userId)
            ->where('beatmap_id', $beatmapId)
            ->first();

        // 3. Cơ chế xác minh điểm số (Score Verification) & Tính toán điểm số mới
        $beatInput = $request->input('beat') ?? $request->input('beats');
        $roundEndless = max(1, (int) (
            $request->input('round_endless')
            ?? $request->input('endless_round')
            ?? $request->input('round')
            ?? $request->input('round_count')
            ?? $request->input('endless_count')
            ?? 1
        ));

        if ($beatInput !== null && is_array($beatInput)) {
            // Đếm số phần tử nhịp đạt chuẩn 1 trong mảng beat
            $countOnes = count(array_filter($beatInput, function ($val) {
                return $val == 1 || $val === '1' || $val === true;
            }));

            // Mỗi 1 beat chuẩn mang giá trị 21 điểm (chuỗi Perfect Combo tối đa 21đ/beat)
            // Trong chế độ Endless, nhân thêm với hệ số số vòng Endless (round_endless)
            $maxAllowedScore = $countOnes * 21 * $roundEndless;

            // Nếu người chơi truyền score lên, kiểm tra xem score có vượt quá Max Allowed Score không
            if ($request->has('score') && $request->score !== null) {
                $submittedScore = (int) $request->score;
                if ($submittedScore > $maxAllowedScore) {
                    return response()->json([
                        'message' => __('api.score.verification_failed') ?? 'Xác minh điểm số thất bại.',
                        'errors'  => [
                            'score' => [
                                __('api.validation.score_exceeds_max', [
                                    'received'  => $submittedScore,
                                    'max_score' => $maxAllowedScore
                                ]) ?? "Điểm số gửi lên ({$submittedScore}) vượt quá giới hạn tối đa cho phép từ mảng beat ({$maxAllowedScore})."
                            ]
                        ]
                    ], 422);
                }
                $newScore = $submittedScore;
            } else {
                // Nếu client chỉ gửi mảng beat mà không truyền score -> Server tự tính điểm chuẩn
                $newScore = $maxAllowedScore;
            }
        } else {
            // Tương thích ngược: Nếu client không gửi mảng beat
            $newScore = (int) ($request->score ?? 0);
        }

        // 4. Kiểm tra điểm số gửi lên so với High Record hiện tại
        if ($existingScore) {
            $isBetterNormalScore = $newScore > $existingScore->score;
            $isBetterHardScore = ($newHardScore !== null && $newHardScore > $existingScore->hard_mode_score);
            $isNewPass = ($isNormalModePassed && !$existingScore->is_normal_mode_passed);

            // Nếu không đạt/không vượt qua kỷ lục ở bất kỳ chỉ số nào -> Bỏ qua không cập nhật, trả về kỷ lục cũ ngay
            if (!$isBetterNormalScore && !$isBetterHardScore && !$isNewPass) {
                $existingScore->load(['user', 'beatmap']);
                return (new UserScoreResource($existingScore))
                    ->additional(['message' => __('api.score.not_beaten') ?? 'Điểm số này chưa vượt qua kỷ lục hiện tại của bạn.'])
                    ->response()
                    ->setStatusCode(200);
            }

            // Nếu đạt kỷ lục mới -> Cập nhật các chỉ số tương ứng
            $updatedData = [];
            if ($isBetterNormalScore) {
                $updatedData['score'] = $newScore;
            }
            if ($isNewPass) {
                $updatedData['is_normal_mode_passed'] = true;
            }
            if ($isBetterHardScore) {
                $updatedData['hard_mode_score'] = $newHardScore;
            }

            $existingScore->update($updatedData);
            $scoreRecord = $existingScore;
            $message = __('api.score.new_record') ?? 'Chúc mừng! Bạn đã phá kỷ lục cá nhân mới.';
            $statusCode = 200;
        } else {
            // Nếu chưa từng chơi bài này -> Tạo mới bản ghi điểm số
            $scoreRecord = UserScore::create([
                'user_id'               => $userId,
                'beatmap_id'            => $beatmapId,
                'score'                 => $newScore,
                'hard_mode_score'       => $newHardScore ?? 0,
                'is_normal_mode_passed' => $isNormalModePassed,
            ]);

            $message = __('api.score.saved') ?? 'Đã lưu điểm số thành công.';
            $statusCode = 201;
        }

        // Nạp kèm thông tin quan hệ để Resource biên dịch dữ liệu chính xác
        $scoreRecord->load(['user', 'beatmap']);

        return (new UserScoreResource($scoreRecord))
            ->additional(['message' => $message])
            ->response()
            ->setStatusCode($statusCode);
    }

    /**
     * Lấy bảng xếp hạng Top 10 kỷ lục điểm cao nhất của một Beatmap
     */
    public function leaderboard(Request $request, $beatmapId)
    {
        $query = UserScore::where('beatmap_id', $beatmapId)
            ->with(['user', 'beatmap']);

        if ($request->query('mode') === 'hard') {
            $query->orderByDesc('hard_mode_score');
        } else {
            $query->orderByDesc('score');
        }

        $scores = $query->orderBy('updated_at')     // Thay vì created_at, ta dùng updated_at vì mốc thời gian kỷ lục mới có thể đã bị thay đổi ở hàm store
            ->limit(10)
            ->get();

        return UserScoreResource::collection($scores);
    }

    /**
     * Xóa điểm kỷ lục (Dành cho Admin khi phát hiện gian lận/khả nghi)
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user() || !$request->user()->is_admin) {
            return response()->json([
                'message' => __('api.auth.unauthorized') ?? 'Bạn không có quyền thực hiện hành động này.'
            ], 403);
        }

        $score = UserScore::find($id);

        if (!$score) {
            return response()->json([
                'message' => __('api.score.not_found') ?? 'Không tìm thấy bản ghi điểm số này.'
            ], 404);
        }

        $score->delete();

        return response()->json([
            'message' => __('api.score.deleted') ?? 'Đã xóa bản ghi điểm số khả nghi thành công.'
        ], 200);
    }
}

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
    /**
     * Lưu hoặc cập nhật điểm số (Chỉ lưu nếu đạt kỷ lục mới - New Personal Best)
     */
    public function store(Request $request)
    {
        // 1. Validate dữ liệu đầu vào gửi lên từ Game Client
        $validator = Validator::make($request->all(), [
            'beatmap_id'            => 'required|exists:beatmaps,id',
            'score'                 => 'nullable|integer|min:0',
            'easy_mode_score'       => 'nullable|integer|min:0',
            'hard_mode_score'       => 'nullable|integer|min:0',
            'asian_mode_score'      => 'nullable|integer|min:0',
            'is_easy_mode_passed'   => 'nullable|boolean',
            'is_normal_mode_passed' => 'nullable|boolean',
            'is_hard_mode_passed'   => 'nullable|boolean',
            'is_asian_mode_passed'  => 'nullable|boolean',
            'beat'                  => 'nullable|array',
            'beats'                 => 'nullable|array',
            'round_endless'         => 'nullable|integer|min:1',
            'endless_round'         => 'nullable|integer|min:1',
            'round'                 => 'nullable|integer|min:1',
            'round_count'           => 'nullable|integer|min:1',
            'endless_count'         => 'nullable|integer|min:1',
            'is_easy_mode'          => 'nullable|boolean',
            'is_hard_mode'          => 'nullable|boolean',
            'is_rage_mode'          => 'nullable|boolean',
            'is_asian_mode'         => 'nullable|boolean',
            'mode'                  => 'nullable|string',
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

        // Bắt buộc phải truyền ít nhất 1 trong các chỉ số điểm hoặc mảng beat
        if (!$request->has('score') && !$request->has('easy_mode_score') && !$request->has('hard_mode_score') && !$request->has('asian_mode_score') && !$request->has('beat') && !$request->has('beats')) {
            return response()->json([
                'errors' => [
                    'score' => [__('api.validation.score_required')]
                ]
            ], 422);
        }

        $userId = $request->user()->id;
        $beatmapId = $request->beatmap_id;

        // X định Mode hiện tại
        $isEasyMode = $request->boolean('is_easy_mode') || $request->input('mode') === 'easy';
        $isHardMode = $request->boolean('is_hard_mode') || $request->boolean('is_rage_mode') || $request->input('mode') === 'hard' || $request->input('mode') === 'rage';
        $isAsianMode = $request->boolean('is_asian_mode') || $request->input('mode') === 'asian';
        $isNormalMode = !$isEasyMode && !$isHardMode && !$isAsianMode;

        // Đọc giá trị điểm số từng mode
        $inputEasyScore = $request->has('easy_mode_score') ? (int) $request->easy_mode_score : ($isEasyMode && $request->has('score') ? (int) $request->score : null);
        $inputHardScore = $request->has('hard_mode_score') ? (int) $request->hard_mode_score : ($isHardMode && $request->has('score') ? (int) $request->score : null);
        $inputAsianScore = $request->has('asian_mode_score') ? (int) $request->asian_mode_score : ($isAsianMode && $request->has('score') ? (int) $request->score : null);
        $inputNormalScore = $request->has('score') ? (int) $request->score : null;

        $isEasyPassed = (bool) ($request->is_easy_mode_passed ?? false);
        $isNormalPassed = (bool) ($request->is_normal_mode_passed ?? false);
        $isHardPassed = (bool) ($request->is_hard_mode_passed ?? false);
        $isAsianPassed = (bool) ($request->is_asian_mode_passed ?? false);

        // 2. Tìm điểm số kỷ lục hiện tại của User trên Beatmap này (nếu có)
        $existingScore = UserScore::where('user_id', $userId)
            ->where('beatmap_id', $beatmapId)
            ->first();

        // 3. Cơ chế xác minh điểm số (Score Verification) & Tính toán điểm số mới
        $beatInput = $request->input('beat') ?? $request->input('beats');

        if ($beatInput !== null && is_array($beatInput)) {
            // Đếm số phần tử nhịp đạt chuẩn 1 trong mảng beat (số tiles đã chạm vào)
            $countOnes = count(array_filter($beatInput, function ($val) {
                return $val == 1 || $val === '1' || $val === true;
            }));

            // Tính điểm tối đa lý thuyết theo logic frontend (Perfect Combo: hit 1 = 2đ, hit 2 = 3đ... hit 20 trở đi = 21đ)
            if ($countOnes < 20) {
                $theoreticalMaxScore = (int) (($countOnes * ($countOnes + 3)) / 2);
            } else {
                $theoreticalMaxScore = 209 + ($countOnes - 19) * 21;
            }

            // Giới hạn điểm số tối đa cho phép
            $maxAllowedScore = $theoreticalMaxScore;

            // Lấy điểm submitted của mode tương ứng để kiểm tra
            $submittedCheckScore = null;
            if ($isEasyMode && $inputEasyScore !== null) $submittedCheckScore = $inputEasyScore;
            elseif ($isHardMode && $inputHardScore !== null) $submittedCheckScore = $inputHardScore;
            elseif ($isAsianMode && $inputAsianScore !== null) $submittedCheckScore = $inputAsianScore;
            elseif ($inputNormalScore !== null) $submittedCheckScore = $inputNormalScore;

            if ($submittedCheckScore !== null && $submittedCheckScore > $maxAllowedScore) {
                return response()->json([
                    'message' => __('api.score.verification_failed') ?? 'Xác minh điểm số thất bại.',
                    'errors'  => [
                        'score' => [
                            __('api.validation.score_exceeds_max', [
                                'received'  => $submittedCheckScore,
                                'max_score' => $maxAllowedScore
                            ]) ?? "Điểm số gửi lên ({$submittedCheckScore}) vượt quá giới hạn tối đa cho phép từ mảng beat ({$maxAllowedScore})."
                        ]
                    ]
                ], 422);
            }

            // Nếu client chỉ gửi mảng beat mà không truyền score -> Tự động dùng điểm tính từ mảng beat
            if ($isEasyMode && $inputEasyScore === null) $inputEasyScore = $theoreticalMaxScore;
            elseif ($isHardMode && $inputHardScore === null) $inputHardScore = $theoreticalMaxScore;
            elseif ($isAsianMode && $inputAsianScore === null) $inputAsianScore = $theoreticalMaxScore;
            elseif ($isNormalMode && $inputNormalScore === null) $inputNormalScore = $theoreticalMaxScore;
        }

        // 4. Kiểm tra điểm số gửi lên so với High Record hiện tại
        if ($existingScore) {
            $updatedData = [];

            // Easy Mode
            if ($inputEasyScore !== null && $inputEasyScore > $existingScore->easy_mode_score) {
                $updatedData['easy_mode_score'] = $inputEasyScore;
            }
            if ($isEasyPassed && !$existingScore->is_easy_mode_passed) {
                $updatedData['is_easy_mode_passed'] = true;
            }

            // Normal Mode (chỉ cập nhật nếu đợt submit này thuộc Normal mode hoặc có score cao hơn)
            if ($inputNormalScore !== null && $isNormalMode && $inputNormalScore > $existingScore->score) {
                $updatedData['score'] = $inputNormalScore;
            }
            if ($isNormalPassed && !$existingScore->is_normal_mode_passed) {
                $updatedData['is_normal_mode_passed'] = true;
            }

            // Hard Mode
            if ($inputHardScore !== null && $inputHardScore > $existingScore->hard_mode_score) {
                $updatedData['hard_mode_score'] = $inputHardScore;
            }
            if ($isHardPassed && !$existingScore->is_hard_mode_passed) {
                $updatedData['is_hard_mode_passed'] = true;
            }

            // Asian Mode
            if ($inputAsianScore !== null && $inputAsianScore > $existingScore->asian_mode_score) {
                $updatedData['asian_mode_score'] = $inputAsianScore;
            }
            if ($isAsianPassed && !$existingScore->is_asian_mode_passed) {
                $updatedData['is_asian_mode_passed'] = true;
            }

            // Nếu không có bất kỳ chỉ số nào thay đổi -> Trả về kết quả hiện tại
            if (empty($updatedData)) {
                $existingScore->load(['user', 'beatmap']);
                return (new UserScoreResource($existingScore))
                    ->additional(['message' => __('api.score.not_beaten') ?? 'Điểm số này chưa vượt qua kỷ lục hiện tại của bạn.'])
                    ->response()
                    ->setStatusCode(200);
            }

            $existingScore->update($updatedData);
            $scoreRecord = $existingScore;
            $message = __('api.score.new_record') ?? 'Chúc mừng! Bạn đã phá kỷ lục cá nhân mới.';
            $statusCode = 200;
        } else {
            // Nếu chưa từng chơi bài này -> Tạo mới bản ghi điểm số với 4 luồng độc lập
            $scoreRecord = UserScore::create([
                'user_id'               => $userId,
                'beatmap_id'            => $beatmapId,
                'easy_mode_score'       => $inputEasyScore ?? 0,
                'is_easy_mode_passed'   => $isEasyPassed,
                'score'                 => $isNormalMode ? ($inputNormalScore ?? 0) : 0,
                'is_normal_mode_passed' => $isNormalPassed,
                'hard_mode_score'       => $inputHardScore ?? 0,
                'is_hard_mode_passed'   => $isHardPassed,
                'asian_mode_score'      => $inputAsianScore ?? 0,
                'is_asian_mode_passed'  => $isAsianPassed,
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

        $mode = strtolower((string) $request->query('mode', 'normal'));

        if ($mode === 'easy') {
            $query->where('easy_mode_score', '>', 0)
                  ->orderByDesc('easy_mode_score');
        } elseif ($mode === 'hard' || $mode === 'rage') {
            $query->where('hard_mode_score', '>', 0)
                  ->orderByDesc('hard_mode_score');
        } elseif ($mode === 'asian') {
            $query->where('asian_mode_score', '>', 0)
                  ->orderByDesc('asian_mode_score');
        } else {
            $query->where('score', '>', 0)
                  ->orderByDesc('score');
        }

        $scores = $query->orderBy('updated_at')     // Mốc thời gian kỷ lục mới
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

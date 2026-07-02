<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Thực thi lệnh Artisan từ giao diện Admin
     */
    public function executeCommand(Request $request)
    {
        // 1. Kiểm tra quyền hạn (Chỉ Admin mới được phép thực thi API này)
        if (!$request->user() || !$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden: Không có quyền truy cập. Chỉ Admin.'], 403);
        }

        // 2. Validate lệnh truyền vào
        $validator = Validator::make($request->all(), [
            'command' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $fullCommand = $request->input('command');

            // 3. Thực thi lệnh (Ví dụ: "user:unban playerone" hoặc "leaderboard:reset 1")
            $exitCode = Artisan::call($fullCommand);
            $output = Artisan::output();

            return response()->json([
                'message'   => 'Lệnh đã được thực thi.',
                'exit_code' => $exitCode,
                'output'    => trim($output) ?: 'Thành công (Không có phản hồi dạng văn bản từ console).'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi thực thi lệnh Artisan.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

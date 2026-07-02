<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserSettingResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserSettingController extends Controller
{
    public function update(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);
        $setting = $user->setting;

        if (!$setting) {
            return response()->json(['message' => 'Settings not found'], 404);
        }

        // ... (Giữ nguyên phần Validator lớn ở câu trả lời trước của bạn vào đây) ...

        $setting->update($request->all());

        return (new UserSettingResource($setting))->additional(['message' => 'Settings updated successfully']);
    }
}

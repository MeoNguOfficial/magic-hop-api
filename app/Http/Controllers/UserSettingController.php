<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserSettingResource;
use App\Http\Requests\UpdateUserSettingRequest;
use Illuminate\Http\Request;

class UserSettingController extends Controller
{
    public function update(UpdateUserSettingRequest $request, string $userId)
    {
        $user = User::findOrFail($userId);
        $setting = $user->setting;

        if (!$setting) {
            return response()->json(['message' => 'Settings not found'], 404);
        }

        // BẢO MẬT 1: Ủy quyền (Authorization) thông qua UserSettingPolicy (Chặn IDOR)
        \Illuminate\Support\Facades\Gate::authorize('update', $setting);

        // BẢO MẬT 2: Cập nhật an toàn với dữ liệu đã được validate từ UpdateUserSettingRequest (Chặn Mass Assignment)
        $setting->update($request->validated());

        return (new UserSettingResource($setting))->additional(['message' => 'Settings updated successfully']);
    }
}

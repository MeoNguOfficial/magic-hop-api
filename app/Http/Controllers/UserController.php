<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Resources\LoginResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Lấy danh sách tất cả người chơi bằng cơ chế Cursor Lazy Loading.
     * Hỗ trợ tìm kiếm theo ID (index) hoặc text và trả về metadata con trỏ.
     */
    public function index(Request $request)
    {
        // Nạp sẵn thông tin cấu hình game (setting) để tránh lỗi N+1 Query
        $query = User::with('setting');
        $perPage = 15; // Số lượng người dùng hiển thị mặc định mỗi trang

        // --- HỖ TRỢ TÌM KIẾM (SEARCH & INDEX) ---
        if ($request->has('search')) {
            $search = $request->input('search');

            // Nếu từ khóa tìm kiếm là số, ưu tiên tìm chính xác theo ID (index) để tối ưu hiệu năng
            if (is_numeric($search)) {
                $query->where('id', $search);
            } else {
                // Nếu là chuỗi, tìm kiếm gần đúng theo username hoặc email
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }
        }

        // --- BẮT ĐẦU ÁP DỤNG CURSOR LAZY LOADING ---
        // Lấy con trỏ (ID của user cuối cùng ở trang trước) gửi từ Client lên
        $cursor = $request->query('cursor');

        if ($cursor && is_numeric($cursor)) {
            // Sắp xếp theo ID giảm dần (id desc) nên các phần tử tiếp theo sẽ có ID nhỏ hơn cursor
            $query->where('id', '<', $cursor);
        }

        // Luôn đi kèm orderBy NGAY TRƯỚC KHI lấy dữ liệu để đảm bảo tính nhất quán của Cursor
        $query->orderBy('id', 'desc');

        // Lấy dư ra 1 bản ghi so với $perPage để kiểm tra xem còn dữ liệu tiếp theo hay không (has_more)
        $users = $query->limit($perPage + 1)->get();

        $hasMore = $users->count() > $perPage;

        if ($hasMore) {
            // Loại bỏ bản ghi thừa thứ $perPage + 1 dùng để check "has_more" ra khỏi tập dữ liệu thực tế
            $users->pop();
        }

        // Lấy ID của phần tử cuối cùng trong trang hiện tại để làm con trỏ (cursor) tiếp theo
        $nextCursor = $users->last()?->id;

        // Trả về dữ liệu kết hợp với Meta Cursor để Frontend quản lý tải/hủy tải
        return UserResource::collection($users)->additional([
            'meta' => [
                'next_cursor' => $hasMore ? $nextCursor : null,
                'has_more'    => $hasMore,
                'per_page'    => $perPage,
                'count'       => $users->count(),
            ]
        ]);
    }

    /**
     * Tạo mới một người dùng (Create)
     */
    public function store(Request $request)
    {
        // 1. Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users,username',
            'realname' => 'nullable|string|max:255',
            'password' => 'required|string|min:6',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'phone'    => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Tạo User mới với ULID làm khóa chính
        $user = User::create([
            'id'       => (string) Str::ulid(),
            'username' => $request->username,
            'realname' => $request->realname,
            'password' => Hash::make($request->password),
            'email'    => $request->email,
            'phone'    => $request->phone,
            'is_actived'     => true, // Kích hoạt ngay sau khi tạo
            'login_attempts' => 0,
            'is_locked'      => false,
            'is_banned'      => false,
        ]);

        // 3. Tạo setting mặc định cho user mới
        $user->setting()->create();

        // 4. Tạo Token Sanctum cho phiên đăng nhập tự động
        $token = $user->createToken($request->input('device_name', 'Game-Client'))->plainTextToken;

        return (new LoginResource($user, $token))
            ->additional(['message' => __('api.user.created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Cập nhật thông tin người dùng (Hỗ trợ cả Admin và User thường)
     * - User thường: Chỉ cập nhật thông tin cá nhân cơ bản và đổi mật khẩu (yêu cầu xác thực mật khẩu cũ)
     * - Admin: Có quyền cập nhật toàn bộ thông tin bao gồm trạng thái khóa, ban, phân quyền
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $currentUser = $request->user();
        $isAdmin = $currentUser && $currentUser->is_admin;

        // 1. Phân quyền: User thường chỉ được sửa chính mình, Admin sửa được bất cứ ai
        if (!$isAdmin && $currentUser->id !== $user->id) {
            return response()->json(['message' => __('api.auth.forbidden')], 403);
        }

        // 2. Khởi tạo quy tắc kiểm duyệt cơ bản
        $rules = [
            'realname' => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'email'    => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
        ];

        if (!$isAdmin) {
            // LUỒNG USER THƯỜNG: Bắt buộc truyền mật khẩu hiện tại khi muốn đổi mật khẩu mới
            $rules['current_password'] = 'required_with:password|string';

            // Chặn tuyệt đối các trường quản trị và trạng thái hệ thống
            $rules['is_banned']      = 'prohibited';
            $rules['banned_until']   = 'prohibited';
            $rules['banned_reason']  = 'prohibited';
            $rules['is_actived']     = 'prohibited';
            $rules['is_admin']       = 'prohibited';
            $rules['login_attempts'] = 'prohibited';
            $rules['is_locked']      = 'prohibited';
            $rules['locked_until']   = 'prohibited';
        } else {
            // LUỒNG ADMIN: Mở tất cả các trường quản trị bao gồm lý do cấm và cơ chế khóa
            $rules['is_banned']      = 'sometimes|boolean';
            $rules['banned_until']   = 'nullable|date';
            $rules['banned_reason']  = 'sometimes|string|max:255';
            $rules['is_actived']     = 'sometimes|boolean';
            $rules['is_admin']       = 'sometimes|boolean';
            $rules['login_attempts'] = 'sometimes|integer|min:0';
            $rules['is_locked']      = 'sometimes|boolean';
            $rules['locked_until']   = 'nullable|date';
        }

        // 3. Tạo thực thể Validator
        $validator = Validator::make($request->all(), $rules);

        // 4. Kiểm tra mật khẩu cũ đối với luồng User thường (Chỉ khi có yêu cầu đổi mật khẩu)
        if (!$isAdmin) {
            $validator->after(function ($validator) use ($request, $currentUser) {
                if ($request->filled('password')) {
                    if (!Hash::check($request->current_password, $currentUser->password)) {
                        $validator->errors()->add('current_password', 'Mật khẩu hiện tại không chính xác.');
                    }
                }
            });
        }

        // 5. Trả lỗi nếu Validation hoặc kiểm tra mật khẩu cũ thất bại
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 6. Chuẩn bị dữ liệu để cập nhật
        $data = $request->only(['realname', 'phone', 'email']);

        // Nếu là Admin, cho phép cập nhật các trường quản trị
        if ($isAdmin) {
            $adminFields = $request->only([
                'is_banned', 
                'banned_until', 
                'banned_reason',  // Thêm trường lý do cấm
                'is_actived', 
                'is_admin',
                'login_attempts',
                'is_locked',
                'locked_until'
            ]);
            $data = array_merge($data, $adminFields);
        }

        // Nếu có yêu cầu đổi mật khẩu, mã hóa và thêm vào dữ liệu cập nhật
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // 7. Thực thi cập nhật xuống Database
        $user->update($data);

        return (new UserResource($user))->additional(['message' => __('api.user.updated')]);
    }

    /**
     * Lấy chi tiết thông tin một người dùng cụ thể
     */
    public function show(string $id)
    {
        $user = User::with('setting')->findOrFail($id);
        return new UserResource($user);
    }
}
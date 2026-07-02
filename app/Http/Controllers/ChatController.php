<?php

namespace App\Http\Controllers;

use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Http\Resources\ChatRoomResource;
use App\Http\Resources\ChatMessageResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    /**
     * 1. USER: Tạo phòng chat hỗ trợ (Ticket Support) kèm phản hồi tự động từ Trợ lý
     */
    public function createRoom(Request $request)
    {
        $user = $request->user();

        // Kiểm tra đầu vào loại hỗ trợ
        $validator = Validator::make($request->all(), [
            'type'  => 'required|string|in:forgot_password,change_password,delete_account,technical,account_issue',
            'title' => 'nullable|string|max:255',
        ], [
            'type.required' => 'Vui lòng chọn danh mục bạn cần hỗ trợ.',
            'type.in'       => 'Danh mục hỗ trợ không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->input('type');
        $title = $request->input('title') ?? $this->getDefaultTitle($type);

        // Sử dụng Transaction để đảm bảo tạo phòng và tin nhắn bot không bị lỗi nửa chừng
        $room = DB::transaction(function () use ($user, $type, $title) {
            // Tạo phòng chat mới
            $chatRoom = ChatRoom::create([
                'user_id'     => $user->id,
                'assigned_to' => null, // Ban đầu chưa có Admin tiếp nhận
                'type'        => $type,
                'title'       => $title,
                'status'      => 'pending', // Trạng thái chờ Admin hoặc đang làm việc với Bot
            ]);

            // Kịch bản tin nhắn tự động từ Trợ lý ảo (Bot) dựa theo phân loại Ticket
            $botMessage = $this->getBotWelcomeMessage($type, $user->username);

            ChatMessage::create([
                'chat_room_id' => $chatRoom->id,
                'sender_id'    => $user->id, // Gán tạm id hệ thống hoặc chính user/admin nhưng đánh dấu type 'system'
                'message'      => $botMessage,
                'type'         => 'system', // Đánh dấu là tin nhắn hệ thống/Bot tự động
                'is_read'      => false,
            ]);

            return $chatRoom;
        });

        return (new ChatRoomResource($room->load(['messages'])))
            ->additional(['message' => 'Yêu cầu hỗ trợ của bạn đã được tiếp nhận.']);
    }

    /**
     * 2. USER & ADMIN: Gửi tin nhắn vào phòng chat (Hỗ trợ văn bản & Upload ảnh đính kèm lên ImgBB)
     * Ưu tiên sử dụng phương thức POST Multipart thuần túy để gửi tệp tin nhị phân trực tiếp,
     * kết hợp truyền tham số expiration (thời gian tự hủy) và name của tệp tin.
     */
    public function sendMessage(Request $request, $roomId)
    {
        $user = $request->user();
        $room = ChatRoom::findOrFail($roomId);

        // Phân quyền bảo vệ: Chỉ người tạo phòng hoặc Admin hệ thống mới được nhắn tin vào đây
        if (!$user->is_admin && $room->user_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền tham gia phòng chat này.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'message'    => 'required_without:image|nullable|string',
            'image'      => 'required_without:message|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:32768', // Cho phép tối đa 32MB theo chuẩn ImgBB
            'type'       => 'sometimes|string|in:text,image',
            'expiration' => 'nullable|integer|between:60,15552000', // Giới hạn từ 1 phút đến 180 ngày (tính bằng giây)
        ], [
            'message.required_without' => 'Nội dung tin nhắn không được để trống khi không có ảnh.',
            'image.required_without'   => 'Vui lòng chọn ảnh hợp lệ khi không có nội dung chữ.',
            'image.image'              => 'Tệp tải lên phải là một hình ảnh.',
            'image.mimes'              => 'Ảnh đính kèm chỉ chấp nhận định dạng jpeg, png, jpg, gif, svg.',
            'image.max'                => 'Kích thước ảnh tối đa cho phép tải lên là 32MB.',
            'expiration.integer'       => 'Thời gian tự hủy của ảnh phải là số nguyên (giây).',
            'expiration.between'       => 'Thời gian tự hủy phải nằm trong khoảng từ 60 giây đến 15,552,000 giây.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $messageContent = $request->input('message');
        $messageType = $request->input('type', 'text');

        // Xử lý upload ảnh đính kèm
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imgbbKey = config('services.imgbb.key') ?? env('IMGBB_API_KEY');
            $expiration = $request->input('expiration');

            if (!empty($imgbbKey)) {
                // KỊCH BẢN 1: Tải lên dịch vụ đám mây ImgBB bằng POST Multipart để bảo vệ dung lượng và bảo mật
                try {
                    // Xây dựng URL chứa API Key và expiration (nếu có) để tránh dính giới hạn độ dài URL của GET request
                    $url = 'https://api.imgbb.com/1/upload?key=' . urlencode($imgbbKey);
                    if ($expiration) {
                        $url .= '&expiration=' . urlencode($expiration);
                    }

                    // Đính kèm tệp tin nhị phân trực tiếp vào luồng dữ liệu (Multipart POST)
                    $response = Http::attach(
                        'image',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )->post($url);

                    if ($response->successful() && $response->json('success')) {
                        // Lấy liên kết ảnh công khai trực tiếp từ ImgBB
                        $messageContent = $response->json('data.url');
                        $messageType = 'image';
                    } else {
                        $errorMsg = $response->json('error.message') ?? 'Không rõ lỗi từ máy chủ ImgBB';
                        return response()->json(['message' => 'Lỗi máy chủ tải ảnh (ImgBB): ' . $errorMsg], 502);
                    }
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Không thể kết nối đến máy chủ ImgBB: ' . $e->getMessage()], 503);
                }
            } else {
                // KỊCH BẢN 2: FALLBACK - Tự động quay về lưu trữ cục bộ (Local Storage) nếu chưa cấu hình Key
                $path = $file->store('chat_attachments', 'public');
                $messageContent = Storage::url($path);
                $messageType = 'image';
            }
        }

        // Tạo tin nhắn mới
        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'sender_id'    => $user->id,
            'message'      => $messageContent,
            'type'         => $messageType,
            'is_read'      => false,
        ]);

        // Nếu phòng đang 'pending' mà Admin nhắn tin -> Tự động chuyển phòng sang 'open' và assign cho Admin đó luôn
        if ($user->is_admin && $room->status === 'pending') {
            $room->update([
                'assigned_to' => $user->id,
                'status'      => 'open'
            ]);
        }

        return new ChatMessageResource($message);
    }

    /**
     * 3. USER & ADMIN: Lấy toàn bộ nội dung hội thoại (Chi tiết phòng chat)
     */
    public function showRoom($roomId)
    {
        $room = ChatRoom::with(['user', 'assignee', 'messages.sender'])->findOrFail($roomId);
        $currentUser = auth()->user();

        if (!$currentUser->is_admin && $room->user_id !== $currentUser->id) {
            return response()->json(['message' => 'Bạn không có quyền xem nội dung này.'], 403);
        }

        // Đánh dấu toàn bộ tin nhắn của đối phương gửi trong phòng này thành "Đã đọc"
        ChatMessage::where('chat_room_id', $room->id)
            ->where('sender_id', '!=', $currentUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return new ChatRoomResource($room);
    }

    /**
     * 4. ADMIN: Lấy danh sách toàn bộ các Ticket yêu cầu hỗ trợ (Hàng đợi ticket)
     */
    public function indexRooms(Request $request)
    {
        // Chỉ admin mới có quyền lấy danh sách toàn bộ phòng chat hỗ trợ
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Quyền truy cập bị từ chối.'], 403);
        }

        $query = ChatRoom::with(['user', 'assignee', 'messages']);

        // Hỗ trợ lọc danh sách theo trạng thái (pending, open, resolved, closed)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Hỗ trợ lọc theo danh mục lỗi chat
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $rooms = $query->orderBy('updated_at', 'desc')->get();

        return ChatRoomResource::collection($rooms);
    }

    /**
     * 5. ADMIN: Tiếp nhận phòng chat hoặc Thay đổi trạng thái Ticket
     */
    public function updateStatus(Request $request, $roomId)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Quyền truy cập bị từ chối.'], 403);
        }

        $room = ChatRoom::findOrFail($roomId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,open,resolved,closed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = ['status' => $request->status];

        // Nếu chuyển trạng thái sang 'open' (tiếp nhận chat) mà chưa gán ai, tự động gán cho Admin hiện tại luôn
        if ($request->status === 'open' && !$room->assigned_to) {
            $updateData['assigned_to'] = $request->user()->id;
        }

        $room->update($updateData);

        return (new ChatRoomResource($room))->additional(['message' => 'Cập nhật trạng thái phòng chat thành công.']);
    }

    /**
     * 6. USER & ADMIN: Xóa tin nhắn đơn lẻ (Thu hồi) & Giải phóng ảnh cục bộ (nếu có)
     */
    public function deleteMessage(Request $request, $messageId)
    {
        $user = $request->user();
        $message = ChatMessage::findOrFail($messageId);

        // Phân quyền xóa tin nhắn: Chỉ chủ nhân gửi tin nhắn đó hoặc Admin mới được phép xóa
        if (!$user->is_admin && $message->sender_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền xóa tin nhắn này.'], 403);
        }

        // Nếu tin nhắn là dạng ảnh đính kèm lưu ở Local Storage, tiến hành dọn dẹp file vật lý
        if ($message->type === 'image') {
            $this->deletePhysicalFile($message->message);
        }

        $message->delete();

        return response()->json(['message' => 'Đã xóa/thu hồi tin nhắn thành công.']);
    }

    /**
     * 7. USER & ADMIN: Xóa toàn bộ phòng chat (Ticket) kèm cascade dọn dẹp ảnh cục bộ
     */
    public function deleteRoom(Request $request, $roomId)
    {
        $user = $request->user();
        $room = ChatRoom::findOrFail($roomId);

        // Phân quyền xóa phòng: Chỉ người chơi tạo phòng hoặc Admin hệ thống mới được xóa
        if (!$user->is_admin && $room->user_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền xóa phòng chat này.'], 403);
        }

        DB::transaction(function () use ($room) {
            // Lấy tất cả tin nhắn dạng ảnh cục bộ trong phòng này để xóa file vật lý trước
            $imageMessages = $room->messages()->where('type', 'image')->get();
            foreach ($imageMessages as $msg) {
                $this->deletePhysicalFile($msg->message);
            }

            // Xóa tất cả tin nhắn thuộc phòng (Cascade)
            $room->messages()->delete();

            // Xóa phòng chat
            $room->delete();
        });

        return response()->json(['message' => 'Đã xóa phòng chat và dọn dẹp toàn bộ dữ liệu lịch sử thành công.']);
    }

    // --- CÁC HÀM TRỢ GIÚP GIAO DIỆN (HELPERS) ---

    /**
     * Trích xuất URL ảnh thô để xóa tệp tin vật lý trong Storage công khai cục bộ
     */
    private function deletePhysicalFile($url)
    {
        if (empty($url)) return;

        // Nếu ảnh được tải lên đám mây ImgBB, không thực hiện xóa cục bộ
        if (str_contains($url, 'ibb.co') || str_contains($url, 'imgbb')) {
            return;
        }

        // Chuyển URL "/storage/chat_attachments/abc.png" thành đường dẫn lưu trữ tương đối "chat_attachments/abc.png"
        $storagePrefix = '/storage/';
        $pos = strpos($url, $storagePrefix);
        if ($pos !== false) {
            $relativePath = substr($url, $pos + strlen($storagePrefix));
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }
    }

    private function getDefaultTitle(string $type): string
    {
        return match ($type) {
            'forgot_password' => 'Yêu cầu khôi phục mật khẩu',
            'change_password' => 'Hỗ trợ thay đổi mật khẩu tài khoản',
            'delete_account'  => 'Yêu cầu đóng/xóa tài khoản vĩnh viễn',
            'account_issue'   => 'Sự cố đăng nhập & bảo mật',
            default           => 'Yêu cầu hỗ trợ kỹ thuật và lỗi game',
        };
    }

    private function getBotWelcomeMessage(string $type, string $username): string
    {
        $base = "🤖 [Trợ Lý Ảo]: Chào bạn **{$username}**! Hệ thống đã ghi nhận yêu cầu của bạn.\n";

        return match ($type) {
            'forgot_password' => $base . "Để khôi phục mật khẩu, vui lòng nhập chính xác địa chỉ Email hoặc Số điện thoại bạn đã liên kết với tài khoản này để nhận mã xác thực OTP nhé.",
            'change_password' => $base . "Để đổi mật khẩu, bạn có thể tự thao tác trong cài đặt. Nếu gặp lỗi, hãy gửi mật khẩu hiện tại và mật khẩu mới muốn thay đổi tại đây để tôi hỗ trợ.",
            'delete_account'  => $base . "⚠️ CẢNH BÁO: Việc xóa tài khoản sẽ làm mất toàn bộ điểm số kỷ lục và cài đặt của bạn. Nếu bạn chắc chắn, hãy nhắn 'XÁC NHẬN XÓA TÀI KHOẢN' kèm lý do nhé.",
            'account_issue'   => $base . "Bạn đang gặp sự cố về tài khoản (bị khóa, lỗi đăng nhập)? Vui lòng cung cấp ảnh chụp màn hình hoặc mô tả chi tiết lỗi để Admin vào xử lý ngay.",
            default           => $base . "Vui lòng mô tả chi tiết lỗi kỹ thuật hoặc sự cố ingame bạn đang gặp phải. Kỹ thuật viên (Admin) sẽ phản hồi bạn trong ít phút.",
        };
    }
}
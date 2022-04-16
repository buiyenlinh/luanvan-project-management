<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;

use App\Model\User;
use App\Model\Chat;
use App\Http\Resources\ChatResource;
use App\Http\Resources\UserResource;

class ChatController extends Controller
{
    use Functions {
        Functions::__construct as parentConstruct;
    }

    public $receiver = null;

    public function __construct() {
        $this->parentConstruct();

        if (request()->hasHeader('Receiver')) {
            $rid = request()->header('Receiver');
            $receiver = User::find($rid);
            if ($receiver) {
                $this->receiver = new UserResource($receiver);
            }
        }
    }

    /**
     * Lấy mã chat. Được tạo từ id của 2 user
     * 
     * @param integer $uid
     * @return string
     */
    private function _getCodeChat($uid = 0) {
        if (!$uid AND !$this->receiver) {
            return '';
        }

        if (!$uid) {
            $uid = $this->receiver->id;
        }

        return min($this->auth->id, $uid) . '_' . max($this->auth->id, $uid);
    }

    /**
     * Lấy tin nhắn mới nhất của user
     *
     * @param integer $uid
     * @return array|null
     */
    public function _getLastestMessage($uid, $check = '') {
        $code = $this->_getCodeChat($uid);
        $data = null;

        $chat = Chat::where('code', $code)->orderBy('id', 'desc')->first();
        if ($chat) {
            $me = $chat->sender == $this->auth->id;
            if ($check == 'via-realtime') {
                $me = $chat->sender == $uid;
            }

            $data = [
                // 'me' => $chat->sender == $this->auth->id,
                'me' => $me,
                'type' => $chat->type,
                'message' => $chat->content,
                'timestamp' => $chat->created_at->timestamp * 1000,
                'test' => $uid
            ];
        }

        return $data;
    }

    /**
     * Lấy số lượng tin nhắn chưa đọc của user
     * 
     * @param integer $uid
     * @return integer
     */
    public function _getNewMessage($uid) {
        $code = $this->_getCodeChat($uid);

        $number = Chat::where([
            ['code', $code],
            ['sender', $uid],
            ['seen', 0]
        ])->count();

        return $number;
    }

    /**
     * Lấy tin nhắn mới nhất và số lượng tin nhắn chưa đọc của user
     * 
     * @param integer $uid
     * @return array
     */
    public function _getLastestAndNew($uid) { // id user khác user login
        $data = [
            'lastest' => $this->_getLastestMessage($uid),
            'new' => $this->_getNewMessage($uid)
        ];

        return $data;
    }

    /**
     * Lấy danh sách user chat
     */
    public function getUserChat(Request $request) {
        $data = [];

        $rows = User::where('id', '!=', $this->auth->id)->orderBy('id', 'asc')->get();
        foreach($rows as $_row) {
            $code = $this->_getCodeChat($_row->id);

            $item = $this->_getLastestAndNew($_row->id);
            $item['user'] = new UserResource($_row);
            $item['code'] = $code;

            $data[$code] = $item;
        }

        return $this->success('', $data);
    }

    
    /**
     * Lấy danh sách tin nhắn chat
     */
    public function getMessageChat(Request $request) {
        if (!$this->receiver) {
            return $this->error('Tài khoản người nhận chưa xác định');
        }

        $page = $request->page;
        $per = $request->per;

        if (!$page) {
            $page = 1;
        }
        
        if (!$per) {
            $per = 20;
        }

        $code = $this->_getCodeChat();

        $db = Chat::where('code', $code);

        $data = [];
        $data['total'] = $db->count();
        $data['last'] = ceil($data['total'] / $per);
        $data['list'] = [];

        $rows = $db->orderBy('id', 'desc')->limit($per)->offset(($page - 1) * $per)->get();
        foreach ($rows as $_row) {
            $data['list'][] = new ChatResource($_row);
        }

        return $this->success('', $data);
    }

    /**
     * Thêm tin nhắn
     */
    public function addMessageChat(Request $request) {
        if (!$this->receiver) {
            return $this->error('Tài khoản người nhận chưa xác định');
        }

        $type = $request->type || 'text';
        $content = $request->content;

        if (!$content) {
            return $this->error('Nội dung tin nhắn là bắt buộc');
        }

        $new_message = Chat::create([
            'code' => $this->_getCodeChat(),
            'sender' => $this->auth->id,
            'type' => $type,
            'content' => $content
        ]);

        $chat = new ChatResource($new_message);

        $this->_sendRealtime([
            'name' => 'chat',
            'notification' => [
                'title' => 'Tin nhắn mới',
                'message' => $content,
            ],
            'code' => $new_message->code,
            'chat' => $chat,
            'new' => $this->_getNewMessage($this->receiver->id),
            'lastest' => $this->_getLastestMessage($this->receiver->id, 'via-realtime')
        ], 'user' . $this->receiver->id);

        $data = [
            'code' => $new_message->code,
            'chat' => $chat,
            'lastest' => $this->_getLastestMessage($this->receiver->id)
        ];

        return $this->success('Thêm tin nhắn thành công', $data);
    }

    /**
     * Xem tin nhắn
     */
    public function seenChat(Request $request) {
        if (!$this->receiver) {
            return $this->error('Tài khoản người nhận chưa xác định');
        }

        $code = $this->_getCodeChat();
        Chat::where('code', $code)
                    ->where('sender', '!=', $this->auth->id)
                    ->where('seen', 0)
                    ->update(['seen' => 1]);

        return $this->success('Đã cập nhật trạng thái xem tin nhắn');
    }
}

<?php 

namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Model\User;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Model\DepartmentUser;
use App\Model\Role;
use Mail;

trait Functions {
  private $auth = null;

  public function __construct() {
    $token = request()->bearerToken();
    if ($token) {
      $user = User::where('token', $token)->first();
      if ($user AND $user->active) {
        $this->auth = new UserResource($user);
      } else {
        abort(401, 'Vui lòng đăng nhập');
      }
    }
  }

  public function success($message, $data = []) {
    return response()->json([
      'status' => 'OK',
      'message' => $message,
      'data' => $data
    ]);
  }

  public function error($error, $data = []) {
    return response()->json([
      'status' => 'ERR',
      'error' => $error,
      'data' => $data
    ]);
  }

  public function _createAuth($user) {
    $avatar = $user->avatar;
    if (!empty($avatar) && !preg_match('/^https?:/', $avatar)) {
      $avatar = Storage::url($avatar);
    }

    $department_user = DepartmentUser::where('user_id', $user->id)->where('leader', 1)->where('active_leader', 1)->latest('id')->first();
    $leader = false;
    if ($department_user) $leader = true;

    return [
      'id' => $user->id,
      'fullname' => $user->fullname,
      'username' => $user->username,
      'email' => $user->email,
      'phone' => $user->phone,
      'active' => $user->active,
      'gender' => $user->gender,
      'birthday' => date("Y-m-d", $user->birthday),
      'avatar' => $avatar,
      'role' => new RoleResource($user->role()->first()),
      'token' => $user->token,
      'leader' => $leader,
      'created_at' => $user->created_at,
      'updated_at' => $user->updated_at
    ];
  }

  /**
   * Lấy danh sách role
   */
  public function getRoleList() {
    return $this->success('Danh sách quyền', Role::all());
  }

  /**
   * Kiểm tra có phải Admin
   */
  public function isAdmin() {
    return $this->auth->role->level == 1 || $this->auth->role->level == 2;
  }

  /**
   * Kiểm tra có phải Manager
   */
  public function isManager() {
    return $this->auth->role->level == 3;
  }

  /**
   * Kiểm tra có phải nhân viên
   */
  public function isUser() {
    return $this->auth->role->level == 4;
  }

  /**
   * Kiểm tra có phải trưởng phòng không
   */
  public function isLeader() {
    $department_user = DepartmentUser::where('user_id', $this->auth->id)->first();
    if ($department_user && $department_user->leader) {
      return true;
    }
    return false;
  }

  /**
   * tạo alias
   */
  public function to_slug($str) {
    return Str::slug($str);
  }


  /**
   * Gửi email
   */
  public function _sendEmail($to, $subject, $content) {
    // Mail::send([], [], function($message) use ($to, $subject, $content) {
    //     $message->to($to)
    //         ->subject($subject)
    //         ->setBody($content, 'text/html');
    // });
  }

  /**
   * Tạo event
   * 
   * @param array $data
   * @param string|array $rooms
   * @param boolean $skip
   * 
   * @return void
   */
  public function _sendRealtime($data, $rooms = []) {
    $api = 'http://localhost:3000/api';
    if (is_string($rooms)) {
      $rooms = !empty($rooms) ? [$rooms] : [];
    }

    $param = [
      'event' => 'realtime',
      'rooms' => $rooms,
      'data' => $data
    ];

    Http::post($api, $param);
  }
  
}
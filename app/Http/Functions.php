<?php 

namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/-/', '', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return $str;
  }


  /**
   * Gửi email
   */
  public function _sendEmail($to, $subject, $content) {
    Mail::send([], [], function($message) use ($to, $subject, $content) {
        $message->to($to)
            ->subject($subject)
            ->setBody($content, 'text/html');
    });
  }
  
}
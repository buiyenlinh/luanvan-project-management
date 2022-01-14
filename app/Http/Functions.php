<?php 

namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Model\User;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Model\Role;

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
      'active' => $user->active,
      'gender' => $user->gender,
      'birthday' => $user->birthday,
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
}
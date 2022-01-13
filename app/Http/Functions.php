<?php 

namespace App\Http;

use Illuminate\Http\Request;
use App\Model\User;
use App\Http\Resources\RoleResource;
use App\Model\Role;

trait Functions {
  private $auth = null;

  public function __contructor() {
    $token = request()->bearerToken();
    if ($token) {
      $user = User::where('token', $token)->first();
      if ($user) {
        $this->auth = $user->toArray();
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
    return [
      'id' => $user->id,
      'fullname' => $user->fullname,
      'username' => $user->username,
      'email' => $user->email,
      'active' => $user->active,
      'gender' => $user->gender,
      'birthday' => $user->birthday,
      'avatar' => $user->avatar,
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
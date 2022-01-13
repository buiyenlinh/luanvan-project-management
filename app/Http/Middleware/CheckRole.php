<?php

namespace App\Http\Middleware;

use Closure;
use App\Model\Role;
use App\Model\User;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $level = null)
    {
        $user = User::where('token', $request->bearerToken())->first();
        if (isset($user->active)) {
            if ($user->active) {
                $role = Role::find($user->role_id);
                $list = explode("|", $level);
                if (!in_array($role->level, $list)) {
                    return response()->json([
                        'status' => 'ERR',
                        'message' => 'Không có quyền truy cập'
                    ]);
                }
            }
        }
        return $next($request);
    }
}

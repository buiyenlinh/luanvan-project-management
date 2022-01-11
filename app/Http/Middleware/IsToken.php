<?php

namespace App\Http\Middleware;

use Closure;
use App\Model\Role;
use App\Model\User;

class IsToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $check = 0;
        $user = User::where('token', $request->bearerToken())->first();
        if (isset($user->active)) {
            if ($user->active) {
                $role = Role::find($user->role_id);
                $check = $role->level;
            }
        }

        if ($check == 0) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'success' => false
            ]);
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        if (!in_array($user->role, $roles)) {
            $message = 'Forbidden';
            if (in_array('admin', $roles) && $user->role !== 'admin') {
                $message = 'Forbidden, This resource is only accessible by admin.';
            }
            return response()->json(['message' => $message], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin() || ! $user->is_active) {
            return redirect()->route('admin.login');
        }
        return $next($request);
    }
}

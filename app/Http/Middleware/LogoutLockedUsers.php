<?php

namespace TLCMap\Http\Middleware;

use Closure;
use Auth;

class LogoutLockedUsers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if (($user && !$user->isLocked()) || !$user) return $next($request);
        Auth::logout(); 
        return redirect('login');
    }
}

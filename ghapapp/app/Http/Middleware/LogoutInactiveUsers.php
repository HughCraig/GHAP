<?php

namespace TLCMap\Http\Middleware;

use Closure;
use Auth;

class LogoutInactiveUsers
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
        $user = Auth::user();
        if (($user && $user->isActive()) || !$user) return $next($request); //if logged in check if active, if not logged in continue anyways as each page handles that
        Auth::logout(); //if user has inactive set to 1, log them out
        return redirect('login');
    }
}

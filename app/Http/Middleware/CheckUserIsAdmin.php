<?php

namespace TLCMap\Http\Middleware;

use Closure;
use Auth;

class CheckUserIsAdmin
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
        Auth::user()->authorizeRoles(['ADMIN', 'SUPER_ADMIN']);
    }
}

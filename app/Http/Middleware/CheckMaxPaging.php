<?php

namespace TLCMap\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CheckMaxPaging
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
        $MAX_PAGING = config('app.maxpaging');
        $DEFAULT_PAGING = config('app.defaultpaging');

        if ($request->input('page')) return $next($request); //if we were simply just accessing the next page

        //Will enter this block on the second redirect here
        if ($request->session()->has('paging.redirect.success')) { //success var is set in GazetteerController@maxPagingRedirect so we don't just redirect AGAIN
            $request->session()->forget('paging.redirect.success');
            return $next($request);
        }

        //Will neter this block on the first redirect IF paging parameter is > max
        if ($request->paging && $request->paging > $MAX_PAGING) {
            session(['paging.redirect.url' => $request->fullUrl()]);
            return redirect()->route('maxPagingMessage');
        }

        return $next($request);
    }
}

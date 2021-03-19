<?php

namespace App\Http\Middleware;

use App\Http\Helpers\Transformer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = 'api')
    {
        if (!Auth::guard($guard)->check()) {
            return Transformer::failed('Only for authenticated user.', null, 401);
        }

        return $next($request);
    }
}

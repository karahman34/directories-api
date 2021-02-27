<?php

namespace App\Http\Middleware;

use App\Http\Helpers\Transformer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Json Response structure.
     *
     * @return  Illuminate\Http\JsonResponse
     */
    private function errorResponse()
    {
        return Transformer::failed('Only for authenticated user.', null, 401);
    }

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
        $auth = Auth::guard($guard);
        $authenticated = $auth->check();

        if (!$authenticated) {
            return $this->errorResponse();
        } else {
            $payload = $auth->payload();

            if ($auth->user()->token !== $payload->get('token')) {
                return $this->errorResponse();
            }
        }

        return $next($request);
    }
}

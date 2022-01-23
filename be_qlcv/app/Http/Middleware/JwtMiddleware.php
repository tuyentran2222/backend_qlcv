<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
class JwtMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return Helper::getResponseJson(400, "Token is invalid");
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return Helper::getResponseJson(400, "Token is expired");
            }else {
                return Helper::getResponseJson(400, "Authorization Token not found");
            }
        }
        return $next($request);
    }
}

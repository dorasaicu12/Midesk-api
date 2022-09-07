<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Http\Functions\MyHelper;
class JWTAuthentication
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
        try{
            $user=JWTAuth::parseToken()->authenticate();
            if (! $token = JWTAuth::parseToken()) {
                return MyHelper::response(false,'please provide a token',[],401);
            }
        }catch(\Exception $e){
            if($e instanceof TokenExpiredException) {
                return MyHelper::response(false,'token expired',[],401);
            }else if($e instanceof TokenInvalidException){
                return MyHelper::response(false,'token invalid',[],401);
            }else{
                return MyHelper::response(false,'token not found',[],401);
            }
        }
        return $next($request);
    }

    protected function shouldPassThrough($request)
    {
        $routePath = $request->path();
        $exceptsPAth = [
            'api/v3/auth/login',
            'api/v3/auth/logout',
        ];
        return in_array($routePath, $exceptsPAth);
    }
}
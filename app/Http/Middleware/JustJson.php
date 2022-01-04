<?php

namespace App\Http\Middleware;
use Closure;

class JustJson
{
    /**
     * We only accept json
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // if ($request->server('CONTENT_TYPE') != 'application/json' && $request->server('REQUEST_METHOD') == 'POST') {
        //     return response(['message' => 'Only JSON requests are allowed'], 406);
        // }
        // return $next($request); 
    }
}
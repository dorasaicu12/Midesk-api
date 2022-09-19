<?php

namespace App\Http\Middleware;

use Closure;

class cors
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
        return $next($request)
        //file config middleware back-end để font-end có thể truy cập tới api dưới local
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Headers','*')
        ->header('Access-Control-Allow-Credentials',' true');
    }
}
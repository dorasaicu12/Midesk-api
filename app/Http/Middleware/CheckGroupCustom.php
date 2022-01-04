<?php

namespace App\Http\Middleware;

use App\Http\Functions\MyHelper;
use Closure;
use Auth;

class CheckGroupCustom
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
        // $group_custom = ['2','103'];
        // $group_id = auth()->user()->groupid;
        // if (in_array($group_id, $group_custom)) {

        // }else{
        //     return MyHelper::response(false,'Failed',[],403);
        // }
        return $next($request);
    }
}
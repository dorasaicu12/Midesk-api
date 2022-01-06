<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class CheckCustomer
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
        /// check customer custom api then redirect to config controller in config/app.php
        $customer_use = config('app.customer');
        $groupid = auth::user()->groupid;
        if (array_key_exists($groupid, $customer_use)) {
            $customer = $customer_use[$groupid];
        }else{
            return $next($request);
        }
        $route = request()->route();
        $req_action = request()->route()->getAction();
        if ($customer) {
            foreach ($customer as $key => $names) {
                if (array_key_exists('as', $req_action) && $key == $req_action['as']) {
                    $control = explode('@',$req_action['controller']);
                    $control = $control[0];
                    $routeAction = array_merge($req_action, [
                        'uses'       => $control.'@'.$names,
                        'controller' => $control.'@'.$names,
                    ]);
                    $route->setAction($routeAction);
                    $route->controller = false;
                }
            }
        }
        return $next($request);
    }
}
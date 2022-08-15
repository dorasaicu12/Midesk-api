<?php

namespace App\Http\Middleware;

use App\Http\Functions\AuthUser;
use App\Http\Functions\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param array                    $args
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, ...$args)
    {
        if (!empty($args) || $this->shouldPassThrough($request)) {
            return $next($request);
        }

        //Group admin

        // if (AuthUser::user()->isAdmin()) {
        //     return $next($request);
        // }
        if (!AuthUser::user()) {
            return $next($request);
        }
        $Permissions = AuthUser::user()->allPermissions()->toArray();
        if (!$Permissions) {
            return Permission::error();
        }else{
            $routePath = $request->path();
            $newPermissions = [];
            $methods = ['get' => 'view', 'post' => 'add', 'put' => 'edit', 'delete' => 'delete'];
            $method = strtolower($request->method());
            $route = explode('.', request()->route()->getAction()['as'])[0];
            $route = $this->convertRoute($route);
            foreach ($Permissions as $key => $permission) {
                $newPermissions[$permission['page']][] = $permission['action'];
            }
            if (array_key_exists($route, $newPermissions)) {
                $method = $methods[$method];
                if (in_array($method, $newPermissions[$route])) {
                    return $next($request);
                }else{
                    return Permission::error();
                }
            }

        }
        return $next($request);
    }


    /**
     * Determine if the request has a URI that should pass through verification.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function convertRoute($route)
    {
        $args = [
                    'event' => 'other_event',
                    'ticket' => 'ticket',
                    'contact' => 'contact',
                    'customer' => 'customer',
                    'order' => 'order',
                    'product' => 'product',
                    'ticketCategory' => 'ticketCategory',
                    'agent' => 'agent',
                    'chat' => 'chat',
                    'chatdetail'=>'chatdetail',
                    'marco'=>'marco',
                    'quickchat'=>'quickchat',
                    'label'=>'label',
                ];
        return $args[$route];
    }

    /**
     * Determine if the request has a URI that should pass through verification.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
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
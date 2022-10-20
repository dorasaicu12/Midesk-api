<?php

namespace App\Http\Functions;

use Auth;

/**
 * Class AuthUser.
 */
class AuthUser
{

    public static function user()
    {
        return auth()->user();
    }

    public static function isLoginPage()
    {
        return (request()->route()->getName() == 'admin.login');
    }

    public static function isLogoutPage()
    {
        return (request()->route()->getName() == 'admin.logout');
    }
}
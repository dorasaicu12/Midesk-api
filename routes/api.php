<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v3'], function () {

    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', 'AuthController@login');
    });

    $namespace = 'v3';
    $group_custom = config('app.group');
    if (auth()->user() && in_array($group_id = auth()->user()->groupid, $group_custom)) {
        $namespace = 'Group_'.$group_id;

        if (!file_exists(app_path('Http/Controllers/'.$namespace))) {
            $namespace = 'v3';
        }
    }

    Route::group(['middleware' => 'auth:api','namespace' => $namespace], function () {

        Route::get('ticket/assignForm', 'TicketController@assignForm');
        Route::post('ticket/comment/{id}', 'TicketController@comment');
        Route::post('ticket/attachfile/{id}', 'TicketController@attachfile');
        Route::apiResource('ticket', 'TicketController')->except('create','edit');

        Route::apiResource('contact', 'ContactController')->except('create','edit');
        Route::apiResource('customer', 'CustomerController')->except('create','edit');
        Route::apiResource('product', 'ProductController')->only('store');        
        Route::apiResource('order', 'OrderController')->except('create','edit');

    });

});




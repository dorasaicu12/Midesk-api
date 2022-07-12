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

    Route::group(['middleware' => ['auth:api','CheckCustomer'],'namespace' => 'v3'], function () {

        Route::get('ticket/ticketForm', 'TicketController@assignForm');
        Route::get('ticket/macro', 'TicketController@marcoList');
        Route::post('ticket/comment/{id}', 'TicketController@comment');
        Route::post('ticket/attachfile/{id}', 'TicketController@attachfile');
        Route::apiResource('ticket', 'TicketController')->except('create','edit');

        Route::apiResource('contact', 'ContactController')->except('create','edit');
        Route::apiResource('customer', 'CustomerController')->except('create','edit');
        Route::apiResource('product', 'ProductController')->only('store');   

        Route::apiResource('order', 'OrderController');
        Route::get('event/eventForm', 'EventController@eventForm')->name('event.form');
        Route::apiResource('event', 'EventController');
        Route::apiResource('agent', 'UserController');

    });

});




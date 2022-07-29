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

    Route::group(['middleware' => ['auth:api', 'CheckCustomer'], 'namespace' => 'v3'], function () {

        Route::get('ticket/forAbi', 'TicketController@ticketForAbi')->name('ticket.forAbi');
        Route::get('ticket/ticketForm', 'TicketController@assignForm');
        Route::get('ticket/macro', 'TicketController@marcoList');
        Route::get('ticket/macro', 'TicketController@macroList')->name('ticket.macro');
        Route::get('ticket/ticketForm', 'TicketController@ticketForm')->name('ticket.form');
        Route::post('ticket/comment/{id}', 'TicketController@comment')->name('ticket.comment');
        Route::post('ticket/attachfile/{id}', 'TicketController@attachfile')->name('ticket.file');
        Route::apiResource('ticket', 'TicketController');
        Route::apiResource('ticketCategory', 'TicketCategoryController');

        Route::apiResource('contact', 'ContactController');
        Route::apiResource('customer', 'CustomerController');
        Route::apiResource('product', 'ProductController');

        Route::apiResource('order', 'OrderController');
        Route::get('event/eventForm', 'EventController@eventForm')->name('event.form');
        Route::post('event/ticket/{id}', 'EventController@EventTicket')->name('event.ticket');


        Route::apiResource('chat', 'ChatController');

        Route::apiResource('chatdetail', 'MessageController');
        
        Route::apiResource('event', 'EventController');
        Route::apiResource('agent', 'UserController');
    });
});
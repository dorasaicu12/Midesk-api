<?php

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
        Route::post('login', 'AuthController@login')->name('login');
        Route::post('refresh/{id}', 'AuthController@refresh')->name('refresh');
    });

    Route::group(['middleware' => ['auth:api', 'CheckCustomer'], 'namespace' => 'v3'], function () {

        Route::get('ticket/forAbi', 'TicketController@ticketForAbi')->name('ticket.forAbi');
        Route::get('ticket/ticketForm', 'TicketController@assignForm');
        Route::get('ticket/macro', 'TicketController@marcoList');
        Route::get('ticket/macro', 'TicketController@macroList')->name('ticket.macro');
        Route::get('ticket/ticketForm', 'TicketController@ticketForm')->name('ticket.form');
        Route::post('ticket/comment/{id}', 'TicketController@comment')->name('ticket.comment');
        Route::post('ticket/attachfile/{id}', 'TicketController@attachfile')->name('ticket.file');
        Route::post('ticket/ticketMerge/{id}', 'TicketController@ticketMerge')->name('ticket.merge');

        Route::apiResource('ticket', 'TicketController');
        Route::apiResource('ticketCategory', 'TicketCategoryController');

        Route::apiResource('contact', 'ContactController');
        Route::get('contact/activities/{id}', 'ContactController@ContactAct')->name('contact.activity');
        Route::get('contact/ticket/{id}', 'ContactController@ContactTicket')->name('contact.ticket');
        Route::apiResource('customer', 'CustomerController');
        Route::get('customer/ticket/{id}', 'CustomerController@customerTicket')->name('customer.ticket');
        Route::apiResource('product', 'ProductController');

        Route::apiResource('order', 'OrderController');
        Route::get('event/eventForm', 'EventController@eventForm')->name('event.form');
        Route::post('event/ticket/{id}', 'EventController@EventTicket')->name('event.ticket');

        Route::apiResource('chat', 'ChatController');
        Route::get('chat/chatdetail/{id}', 'MessageController@chatlist')->name('chat.detail');
        Route::post('upload', 'MessageController@upload')->name('upload.file');
        Route::apiResource('chatdetail', 'MessageController');
        Route::get('chat/pagecheck/{id}', 'ChatController@PageCheck')->name('chat.check');

        Route::apiResource('marco', 'MarcoController');
        Route::apiResource('quickchat', 'QuickChatController');
        Route::apiResource('label', 'LabelController');

        Route::apiResource('event', 'EventController');
        Route::apiResource('agent', 'UserController');

        Route::apiResource('tag', 'TagController');

        Route::get('notification/{id}', 'NotificationController@GetNoti')->name('notification');

    });
});

Route::group(['prefix' => 'v2', 'middleware' => ['auth:api', 'CheckCustomer'], 'namespace' => 'v2'], function () {
    Route::apiResource('ticket', 'TicketController');
    Route::get('ticket/ticketFollow/{id}', 'TicketController@getTicketFollow')->name('ticket.follow');
    Route::get('ticket/ticketTeam/{id}', 'TicketController@TicketOfTeam')->name('ticket.team');
    Route::get('pending/ticket', 'TicketController@TicketPending')->name('ticket.pending');
    Route::get('deleteList/ticket', 'TicketController@getDefaultDelete')->name('ticket.deleteList');
    Route::get('getall/ticket', 'TicketController@GetAllThroughtPermission')->name('ticket.all');
});
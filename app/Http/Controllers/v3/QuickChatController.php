<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


use App\Http\Functions\MyHelper;
use App\Models\QuickChat;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Ticket;
use App\Traits\ProcessTraits;
use Illuminate\Support\Facades\Log;

use App\Http\Functions\CheckField;

use Auth;
use DB;

class QuickChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
    * @OA\Get(
    *     path="/api/v3/quickchat",
    *     tags={"quickchat"},
    *     summary="Fget all quickchat",
    *     description="<h2>This API will get all quickchat</h2>",
    *     operationId="show",
     *     @OA\Parameter(
    *         name="page",
    *         in="query",
    *         required=false,
    *         explode=true,
    *         example=1,
    *         description="<h4>Number of page to get</h4>
                    <code>Type: <b id='require'>Number</b></code>"
    *     ),
    *     @OA\Parameter(
    *         name="limit",
    *         in="query",
    *         required=false,
    *         explode=true,
    *         example=5,
    *         description="<h4>Total number of records to get</h4>
                <code>Type: <b id='require'>Number<b></code>"
    *     ),
    *     @OA\Parameter(
    *         name="search",
    *         in="query",
    *         example="firstname<=>Nhỡ",
    *         description="<h4>Find records with condition get result desire</h4>
                    <code>Type: <b id='require'>String<b></code><br>
                    <code>Seach type supported with <b id='require'><(like,=,!=,beetwen)></b> </code><br>
                    <code>With type search beetwen value like this <b id='require'> created_at<<beetwen>beetwen>{$start_date}|{$end_date}</b> format (Y/m/d H:i:s)</code><br>
                    <code id='require'>If multiple search with connect (,) before</code>",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Parameter(
    *         name="order_by",
    *         in="query",
    *         example="id:DESC",
    *         description="<h4>Sort records by colunm</h4>
                    <code>Type: <b id='require'>String</b></code><br>
                    <code>Sort type supported with <b id='require'>(DESC,ASC)</b></code><br>
                    <code id='require'>If multiple order with connect (,) before</code>",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Parameter(
    *         name="fields",
    *         in="query",
    *         required=false,
    *         explode=true,
    *         example="fullname,phone,email",
    *         description="<h4>Get only the desired columns</h4>
                    <code>Type: <b id='require'>String<b></code>"
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="string", example="1"),
    *             @OA\Property(property="groupid",type="string", example="2"),
    *             @OA\Property(property="title",type="string", example="this is example ticket"),
    *             @OA\Property(property="content",type="string", example="example content"),
    *             @OA\Property(property="requester",type="string", example="3"),
    *             @OA\Property(property="get_tickets_detail",type="array", 
    *               @OA\Items(type="object",
    *                 @OA\Property(property="id",type="string", example="1"),
    *                   @OA\Property(property="title",type="string", example="this is title example"),
    *                   @OA\Property(property="content",type="string", example="this is content example"),
    *                 ),
    *               ),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Invalid chats",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="boolean", example="chats not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         )
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     }
    * )
    */
    public function index(Request $request)
    {
        $req = $request->all();
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $checkFileds= CheckField::check_fields($req,'premade');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds= CheckField::check_order($req,'premade');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds= CheckField::CheckSearch($req,'premade');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
             $checksearch= CheckField::check_exist_of_value($req,'premade');

             if($checksearch){
                return MyHelper::response(false,$checksearch,[],404);
             }
           }    
        $chat = new QuickChat;
        $chat  = $chat->getListDefault($req);
        return MyHelper::response(true,'Successfully',$chat,200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

         /**
    * @OA\Get(
    *     path="/api/v3/quickchat/{chatId}",
    *     tags={"quickchat"},
    *     summary="Find quick chat by chatId",
    *     description="<h2>This API will find chat by {chatId} and return only a single record</h2>",
    *     operationId="show",
    *     @OA\Parameter(
    *         name="chatId",
    *         in="path",
    *         description="<h4>This is the id of the chat you are looking for</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         example=1,
    *         required=true,
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="string", example="1"),
    *             @OA\Property(property="groupid",type="string", example="2"),
    *             @OA\Property(property="title",type="string", example="this is example ticket"),
    *             @OA\Property(property="content",type="string", example="example contetnt"),
    *             @OA\Property(property="public",type="string", example="public string"),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Invalid chat ID",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="boolean", example="chat not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         )
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     }
    * )
    */
    
    public function show($id)
    {
        $Chats=new QuickChat;
        $Chats=$Chats->ShowOne($id);
        if(!$Chats){
            return MyHelper::response(false,'quick chat not found',[],404);
        }
        return MyHelper::response(true,'Successfully',[$Chats],200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
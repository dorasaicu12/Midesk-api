<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Functions\MyHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\CustomField;
use App\Traits\ProcessTraits;
use Carbon\Carbon;
use App\Http\Requests\ContactRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Auth;
use DB;
use App\Models\actionLog;
use App\Models\customerContactRelation;

use App\Models\Chat;

use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

      /**
    * @OA\Get(
    *     path="/api/v3/chat",
    *     tags={"Chat"},
    *     summary="Get list chat",
    *     description="<h2>This API will Get list chat with condition below</h2>",
    *     operationId="index",
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
    *             @OA\Property(property="status", type="boolean", example="true"),
    *             @OA\Property(property="message", type="string", example="Successfully"),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="data",type="object",
    *                   @OA\Property(property="id",type="string", example="1"),
    *                   @OA\Property(property="groupid",type="string", example="1"),
    *                   @OA\Property(property="channle",type="string", example="văn A"),
    *                   @OA\Property(property="email",type="string", example="Nguyễn"),
    *                   @OA\Property(property="phone",type="string", example="0987654321"),
    *                   @OA\Property(property="flag",type="string", example="abcxyz@gmail.com"),
    *                 ),
    *                 @OA\Property(property="current_page",type="string", example="1"),
    *                 @OA\Property(property="first_page_url",type="string", example="null"),
    *                 @OA\Property(property="next_page_url",type="string", example="null"),
    *                 @OA\Property(property="last_page_url",type="string", example="null"),
    *                 @OA\Property(property="prev_page_url",type="string", example="null"),
    *                 @OA\Property(property="from",type="string", example="1"),
    *                 @OA\Property(property="to",type="string", example="1"),
    *                 @OA\Property(property="total",type="string", example="1"),
    *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/contact"),
    *                 @OA\Property(property="last_page",type="string", example="null"),
    *             )
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
       //check column exits

       if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {

        $columns['fields']=Schema::getColumnListing('social_history');
        $checkid=explode(',',$req['fields']);
        if (in_array('key_id',$checkid)){
            $columns['fields']=array_merge($columns['fields'],['key_id']); 
        }
        $f= rtrim($req['fields'],',');
        
        $field= explode(',',$req['fields']);

        $temp = [];
        $message='';
        foreach($field as $key => $value){
            if(!in_array($value, $temp)){
                $temp[]=$value;
                $check_array=in_array($value, $columns['fields']);
                if(!$check_array){
                    $message .='Column '.$value.' can not be found.';
                }else{
                    $message='';
                }
            }
            if($message !=''){
                 $message2=$message;
            } 
        }
            if(isset($message2)){
                return MyHelper::response(false,$message2,[],404);
            }
       }


       if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
        
        $columns['fields']=Schema::getColumnListing('social_history');  
        $message='';
        
        if(strpos($req['search'], '<=>') !== false){
            $key_search = explode('<=>', $req['search']);
            $check_array=in_array($key_search[0], $columns['fields']);
            if(!$check_array){
                $message .='Search column '.$key_search[0].' can not be found.';
            }else{
                $message='';
            }
            
        }else if(strpos($req['search'], '<like>') !== false){
            $key_search = explode('<like>', $req['search']);
            $check_array=in_array($key_search[0], $columns['fields']);
            if(!$check_array){
                $message .='Search column '.$key_search[0].' can not be found.';
            }else{
                $message='';
            }
            
        
            $key_search[1] = '%'.$key_search[1].'%';
        }else if(strpos($req['search'], '<>') !== false){
            $key_search = explode('<>', $req['search']);
            $check_array=in_array($key_search[0], $columns['fields']);
            if(!$check_array){
                $message .='Search column '.$key_search[0].' can not be found.';
            }else{
                $message='';
            }
            
        }

        if($message !== ''){
            return MyHelper::response(false,$message,[],404);
        }
        
    }


       
       if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
        $columns['fields']=Schema::getColumnListing('social_history');  
        $order_by = explode(',', $req['order_by']);
        $message='';
        $temp = [];
        foreach ($order_by as $key => $value) {
            
                $c = explode(':', $value);
                $by = $c[0];
                $order = $c[1];

                if(!in_array($by, $temp)){
                    $temp[]=$by;
                    $check_array=in_array($by, $columns['fields']);
                    if(!$check_array){
                        $message .='Column '.$by.' can not be found.';
                    }else{
                        $message='';
                    }
                }
                if($message !=''){
                     $message2=$message;
                }
        }
        if(isset($message2)){
            return MyHelper::response(false,$message2,[],404);
        }
    }
    
       $chats = (new Chat)->getDefault($req);
       $check[]=$chats;
       
        foreach($chats as $value){
        if(isset($value['channel'])){
            $check_field = Chat::where('id',$value['id'])->first();
            if($value['channel']=='facebook'){
                $value['id_key']=$check_field['fb_key'];
            }elseif($value['channel']=='zalo'){
                $value['id_key']=$check_field['zalo_key'];
            }
            
        }
           }
       return MyHelper::response(true,'Successfully',$chats,200);
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
    public function show($id)
    {
        //
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
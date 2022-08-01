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
use App\Http\Functions\CheckField;

use App\Models\ChatMessage;

use Illuminate\Support\Facades\Schema;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    
    public function index(Request $request)
    {
       $req = $request->all();
       //check column exits
       if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
        $checkFileds= CheckField::check_chat_field($req);
        if($checkFileds){
           return MyHelper::response(false,$checkFileds,[],404);
        }
       }
       
       if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
        $checkFileds= CheckField::check_order($req,'social_message');
         if($checkFileds){
            return MyHelper::response(false,$checkFileds,[],404);
         }
       }
       
       if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
        $checkFileds= CheckField::CheckSearch($req,'social_message');
         if($checkFileds){
            return MyHelper::response(false,$checkFileds,[],404);
         }
       }
       
       $chats = (new ChatMessage)->getDefault($req);
       
        foreach($chats as $value){
            if(isset($value['datecreate'])){
            $value['datecreate']= date('Y-m-d H:i:s',$value['datecreate']);
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
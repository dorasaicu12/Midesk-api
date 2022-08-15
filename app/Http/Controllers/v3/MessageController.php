<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Chat;

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

    public function chatlist($groupid,$id_page,$id_key,Request $request)
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
        
        $chats = (new ChatMessage)->getDefault($req,$groupid,$id_page,$id_key);
        $value2='';
         foreach($chats as $value){
            //  if(isset($value['datecreate'])){
            //  $value['datecreate']= date('Y-m-d H:i:s',$value['datecreate']);
            //  }
            $value2.=$value;
            if(isset($value['url'])){
               $d= ChatMessage::where('id',$value['id'])->first();
                $year_folder  = date('Y',$d['datecreate']);
                $month_folder = date('m',$d['datecreate']);
                $day_folder   = date('d',$d['datecreate']);
                 $u=str_replace(array('[', '\\', ']', '"'), '', $value['url']);
                //  $link_img = "https://cskh.midesk.vn/upload/facebook_image_2021/".$groupid."/".$year_folder."/".$month_folder."/".$day_folder."/".$u;
                 if($value['url'] !=='' && $value['url']!== null){
                    $value['url']=$u;
                 }
                 
            }
            }

                
            if(!$value2){
                return MyHelper::response(false,'Chat does not exits',[],404);
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
    public function show(Request $request,$id)
    {
        
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
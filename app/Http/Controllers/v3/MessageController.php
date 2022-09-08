<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;

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
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File; 

use Illuminate\Routing\Route;
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
        if(!$chats){
            
        }
     
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
                 $u=str_replace(array('[',']','"'), '', $value['url']);
                //  $link_img = "https://cskh.midesk.vn/upload/facebook_image_2021/".$groupid."/".$year_folder."/".$month_folder."/".$day_folder."/".$u;
                 if($value['url'] !=='' && $value['url']!== null){
                    $value['url']= '['.'"'.$u.'"'.']';
                 }
                 
            }
            $channel=$value['channel'];
              if($value['user_id']!==null){
                  $user_id=$value['user_id'];
               }
            }
            
            if(!$value2){
                return MyHelper::response(false,'Chat does not exits',[],404);
            }
            if($channel=='facebook'){
              $username=Chat::where('fb_key',$id_key)->first()->toArray();
              $contact= Contact::where('fullname', 'like', '%' .$username['name']. '%')->first();
              $user=User::where('id',$user_id)->first();
              if(!$user){
                $key=$user_id.'_'.$id_page ;
                  $user=Chat::where('id_page',$id_page)->where('fb_key',$key)->first();
                  $data=[
                    'fullname'=>$user['name'],
                    'groupid'=>$user['groupid'],
                    'datecreate'=>$user['datecreate'],
                    'picture'=>$user['picture']
                  ];
                  $chatDetail['user']=$data;
              }else{
                  $data=[
                      'fullname'=>$user['fullname'],
                      'groupid'=>$user['groupid'],
                      'datecreate'=>$user['datecreate'],
                      'picture'=>$user['picture']
                    ];
                    $chatDetail['user']=$data;
              }
              if(!$contact){
                $chatDetail['chat_contact']=[];
              }else{
                $chatDetail['chat_contact']=$contact;
              }
            }elseif($channel=='zalo'){
                
                $user=Chat::where('id_page',$id_page)->where('zalo_key',$id_key)->first();
                $contact= Contact::where('zalo_oaid',$user['zalo_oaid'])->first();
                if(!$user){
                    $chatDetail['user']=[];
                }else{
                    $data=[
                        'fullname'=>$user['name'],
                        'groupid'=>$user['groupid'],
                        'datecreate'=>$user['datecreate'],
                        'picture'=>$user['zalo_avatar']
                      ];
                      $chatDetail['user']=$data;
                }
                if(!$contact){
                  $chatDetail['chat_contact']=[];
                }else{
                  $chatDetail['chat_contact']=$contact;
                }
            }
                
            
            $chatDetail['chat_data']=$chats; 

        return MyHelper::response(true,'Successfully',$chatDetail,200);
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
    public function upload(Request $request){
        $files = $request->file('file')->store('public');
        $r=str_replace(array('public/'), '', $files);
        return MyHelper::response(true,'upload file successfully',[asset('/storage/'.$r)],200);
        exit;
        
        if(!$request->hasFile('file')) {
            return response()->json(['upload_file_not_found'], 400);
        }
        $files = $request->file('file')->store('public'); 
        $r=str_replace(array('public'), 'public/storage', $files);
        try {
            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'http://api.resmush.it',
            ]);
            $response = $client->request('POST', "?qlty=92", [
                'multipart' => [
                    [
                        'name'     => 'files', // name value requires by endpoint
                        'contents' => fopen(base_path().'/'.$r, 'r'),
                        'filename' => $r,
                        'headers'  => array('Content-Type' => mime_content_type(base_path().'/'.$r))
                    ]
                ]
            ]);
            if (200 == $response->getStatusCode()) {
                $response = $response->getBody();
                
                $arr_result = json_decode($response);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        File::delete(base_path().'/'.$r);
        return MyHelper::response(true,'upload file successfully',[$arr_result],200);
exit;

        if(!$request->hasFile('file')) {
            return response()->json(['upload_file_not_found'], 400);
        }
        $files = $request->file('file'); 
        $errors = [];  
        foreach ($files as $file) {      
     
            $extension = $file->getClientOriginalExtension(); 
                foreach($request->file as $mediaFiles) {
                    $path = $mediaFiles->store('public/uploads/'.date('Y').'/'.date('m').'');
                    $name = $mediaFiles->getClientOriginalName();
                }
                $r=str_replace(array('public'), 'storage', $path);
                return MyHelper::response(true,'upload file successfully',['file path'=>$r],200);
     
        }
    }
    
    }
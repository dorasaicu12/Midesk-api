<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Functions\MyHelper;
use App\Models\Macro;
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

class MarcoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $req = $request->all();
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $checkFileds= CheckField::check_fields($req,'macro');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds= CheckField::check_order($req,'macro');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds= CheckField::CheckSearch($req,'macro');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
             $checksearch= CheckField::check_exist_of_value($req,'macro');

             if($checksearch){
                return MyHelper::response(false,$checksearch,[],404);
             }
           }
           
           
        $marco = new Macro;
        $marco  = $marco->getListDefault($req);
        foreach($marco  as $value){
            if(isset($value['action'])){
            $value['action']= json_decode($value['action']);
        }
           }
        return MyHelper::response(true,'Successfully',$marco,200);
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
        $groupid = auth::user()->groupid;
        $title=$request->title;
        $description=$request->description;
        $type=$request->type && 'all';
        $creby   = auth::user()->id;
        $time     = time();
        $public=$request->public && 1;
        $action=$request->action;
     


    DB::beginTransaction();
    try {
    DB::commit();
    $order = new Macro;
    $order->groupid=$groupid;
    $order->title=$title;
    $order->description=$description;
    $order->public=$public;
    
    $order->creby=$creby;
    $order->datecreate=$time;
    if(isset($action)){
        foreach($action as $value){
            if($value['type']=='public_reply_template'){
                $id=$value['value'];
                $conten=DB::table("email_template")->where('id',$id)->first();
                if(!$conten){
                    return MyHelper::response(false,'id:'.$id.' can not be found in field public_reply_template',[],404);
                }
                $value['value']= $conten->email_content;       
            }
            $actionPut[]=$value;
            
        }
        $order->action=json_encode($actionPut);
}
    $order->save();
    return MyHelper::response(true,'Created Macro successfully',['id'=>$order->id],200);
} catch (\Exception $ex) {
    DB::rollback();
    return MyHelper::response(false,$ex->getMessage(), [],403);
}
   
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id_macro)
    {
        $marco = (new Macro)->checkExist($id_macro);
        if(!$marco){
            return MyHelper::response(false,'Macro not found',[],404);
        }else{
            
                if(isset($marco['action'])){
                $marco['action']= json_decode($marco['action']);
            }
       
            return MyHelper::response(true,'successfully',$marco,200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id_macro)
    {
       
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id,Request $request)
    {
        $data = array_filter($request->all());
        $macroupdate = Macro::find($id);
        if(isset($data['action'])){  
            foreach($data['action'] as $value){
                if($value['type']=='public_reply_template'){
                    $id=$value['value'];
                    $conten=DB::table("email_template")->where('id',$id)->first();
                    if(!$conten){
                        return MyHelper::response(false,'id:'.$id.' can not be found in field public_reply_template',[],404);
                    }
                    $value['value']= $conten->email_content;       
                }
                $actionPut[]=$value;
                
            }
            $data['action']=json_encode($actionPut);
    }
       $data['dateupdate']=time();
       $macroupdate->update($data);

    return MyHelper::response(true,'updated Macro successfully',[],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $Macro = (new Macro)->checkExist($id);
        if (!$Macro) {
            return MyHelper::response(false,'Macro Not Found', [],404);
        }else{
            $Macro->delete();
        }
        return MyHelper::response(true,'Delete Macro Successfully', [],200);
    }
}
<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;

use App\Http\Functions\MyHelper;
use App\Http\Functions\CheckField;

use App\Http\Controllers\Controller;
use App\Models\NotificationModel;

class NotificationController extends Controller
{
    public function GetNoti(Request $request,$id){
        $req = $request->all();
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $checkFileds= CheckField::check_fields($req,'notifications');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           
           if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds= CheckField::check_order($req,'notifications');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }         
           
           if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds= CheckField::CheckSearch($req,'notifications');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
             $checksearch= CheckField::check_exist_of_value($req,'notifications');
             if($checksearch){
                return MyHelper::response(false,$checksearch,[],404);
             }
           }
           
           $notifications = (new NotificationModel)->getDefault($req,$id);

           return MyHelper::response(true,'Successfully',[$notifications],200);
    }
}
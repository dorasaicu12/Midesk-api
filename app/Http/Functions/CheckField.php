<?php
namespace App\Http\Functions;
use Illuminate\Support\Facades\Schema;
use App\Http\Functions\MyHelper;

class CheckField{
     static function check_fields($req,$name){
        $columns['fields']=Schema::getColumnListing($name);  
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
                return $message2;
            }
     }

     
     static function check_order($req,$name){
        $columns['fields']=Schema::getColumnListing($name);  
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
                        $message .='Order by column '.$by.' can not be found.';
                    }else{
                        $message='';
                    }
                }
                if($message !=''){
                     $message2=$message;
                }
            

        }
        if(isset($message2)){
            return $message2;
        }
     }

     static function CheckSearch($req,$name){
        $columns['fields']=Schema::getColumnListing($name);  
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
            return $message;
        }
     }
}
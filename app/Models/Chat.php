<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use Illuminate\Support\Facades\Schema;
use App\Http\Functions\MyHelper;

class Chat extends Model
{
    public $timestamps = false;
    protected $table = 'social_history';

    const DELETED = 1;
    const DELETE = [NULL,0];
    const SORT = 'id';
    const ORDERBY = 'datecreate:desc,id:asc';
    const TAKE = 10;
    const FROM = 0;
    
    public $fillable_group = '
     id,name,phone,tag,email,channel,groupid,assign_agent,assign_team,message,id_channel,id_page,datecreate,fb_key,zalo_key
    ';

    function __construct()
    {
        $groupid = auth::user()->groupid;

    }

    public function getDefault($req)
    {
        $res = new self;
        /// paginate
        if (array_key_exists('page', $req) && rtrim($req['page']) != '') {
            $from = intval($req['page']) * self::TAKE;
        }else{
            $from = self::FROM;
        }
        /// litmit ofset
        if (array_key_exists('limit', $req) && rtrim($req['limit']) != '') {
            $limit = $req['limit'];
            if (intval($limit) > 100) {
                $limit = 100;
            }
        }else{
            $limit = self::TAKE;
        }
        /// select
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {

            $array = explode(',',$req['fields']);
            if(in_array('key_id',$array)){
                unset($array[array_search('key_id',$array)]);
                array_push($array,"channel");
                $req['fields']= implode(",",$array);
            }
            $res = $res->selectRaw('id,'.$req['fields']);
        }else{
            if (auth::user()->groupid == '196') {
                $res = $res->selectRaw('id,'.$this->fillable_group);
            }
            $res = $res->selectRaw('id,'.$this->fillable_group);
        }
        
        /// search
    	if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $search = explode(',', $req['search']);
            foreach($search as $value){
                if(strpos($value, '<=>') !== false){
                    $key_search = explode('<=>', $value);
                    $type = '=';
                }else if(strpos($value, '<like>') !== false){
                    $key_search = explode('<like>', $value);
                    $type = 'like';
                    $key_search[1] = '%'.$key_search[1].'%';
                }else if(strpos($value, '<>') !== false){
                    $key_search = explode('<>', $value);
                    $type = '<>';
                }
                $res->where($key_search[0],$type,$key_search[1]);
              }
    	}

        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $order_by = explode(',', $req['order_by']);
            foreach ($order_by as $key => $value) {
                $c = explode(':', $value);
                $by = $c[0];
                $order = $c[1];
                $res = $res->orderBy($by, $order);
            }
        }else{
            $order_by = explode(',', self::ORDERBY);
            foreach ($order_by as $key => $value) {
                $c = explode(':', $value);
                $by = $c[0];
                $order = $c[1];
                $res = $res->orderBy($by, $order);
            }
        }
        $delete = self::DELETE;
        return $res->where(function($q) use ($delete) {
            $q->where('type','inbox');
            })
        ->offset($from)
        ->limit($limit)
        ->paginate($limit)->appends(request()->query());

        //appends(request()->query()) dùng để sinh ra các đường link để paginate trong laravel 
    }
}
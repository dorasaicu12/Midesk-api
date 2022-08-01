<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use Illuminate\Support\Facades\Schema;
use App\Http\Functions\MyHelper;

class ChatMessage extends Model
{
    public $timestamps = false;
    protected $table = 'social_message';

    const DELETED = 1;
    const DELETE = [NULL,0];
    const SORT = 'id';
    const ORDERBY = 'datecreate:desc,id:asc';
    const TAKE = 10;
    const FROM = 0;
    
    public $fillable_group = '
    social_message.id,
    social_message.groupid,
    social_message.channel,
    social_message.name,
    social_message.message,
    social_message.datecreate,
    social_message.type,
    social_message.status,
    table_users.id as userId,
    social_message.replyby,
    social_message.reply
    ';

    function __construct()
    {
        $groupid = auth::user()->groupid;

    }

    public function getDefault($req,$id,$id_page,$channel,$key_id)
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
            if(in_array('table_users.id',$array) && in_array('social_message.id',$array)){
                unset($array[array_search('table_users.id',$array)]);
                array_push($array,"table_users.id as User_id");
                $req['fields']= implode(",",$array);
            }

            $res = $res->selectRaw('table_users.id as id_user,'.'replyby,'.'social_message.id,'.$req['fields']);
        }else{
            if (auth::user()->groupid == '196') {
                $res = $res->selectRaw('social_message.id,'.$this->fillable_group);
            }
            $res = $res->selectRaw('social_message.id,'.$this->fillable_group);
        }
        
        /// search
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {

            if(strpos($req['search'], '<=>') !== false){
                $key_search = explode('<=>', $req['search']);
                $type = '=';
            }else if(strpos($req['search'], '<like>') !== false){
                $key_search = explode('<like>', $req['search']);
                $type = 'like';
                $key_search[1] = '%'.$key_search[1].'%';
            }else if(strpos($req['search'], '<>') !== false){
                $key_search = explode('<like>', $req['search']);
                $type = '<>';
            }
            $res = $res->where($key_search[0],$type,$key_search[1]);
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
                $res = $res->orderBy('social_message.'.$by, $order);
            }
        }
        $delete=1;
        
        return $res->leftJoin('table_users', function($join) {
            $join->on('social_message.replyby', '=', 'table_users.id');
          })
          ->where(function($q) use ($delete) {
            $q->where('type','inbox');
        })->where('chat_id',$id)
        ->where('channel',$channel)
        ->where('id_page',$id_page)
        ->where('key_id',$key_id)
        ->where('social_message.groupid',auth::user()->groupid)
        ->offset($from)
        ->limit($limit)
        ->paginate($limit)->appends(request()->query());

        //appends(request()->query()) dùng để sinh ra các đường link để paginate trong laravel 
    }
}
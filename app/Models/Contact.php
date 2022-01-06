<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;

class Contact extends Model
{
    public $timestamps = false;
    protected $table = 'contact';
    
    protected $guarded = [];
    // protected $fillable = ['contact_id','groupid','firstname','lastname','fullname','phone','email','address','province','creby','datecreate','channel','custom_fields','facebook_id','zalo_id'];

    const DELETED = 1;
    const DELETE = [NULL,0];
    const SORT = 'id';
    const ORDER = 'asc';
    const TAKE = 10;
    const FROM = 0;
    const KEYS = 'fullname';
    public $fillable_group = '';

    function __construct()
    {
        $groupid = auth::user()->groupid;
        if ($groupid == '196') {
            $this->fillable_group = 'ext_contact_id,
                                    phone_other,
                                    phone,
                                    fullname,
                                    birthday,
                                    province,
                                    email,
                                    branch,
                                    card_type,
                                    creator,
                                    created,
                                    identity_number,
                                    identity_date,
                                    identity_location,
                                    gender';
        }
        self::setTable('contact_'.$groupid);
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
            $res = $res->selectRaw('id,'.$req['fields']);
        }else{
            if (auth::user()->groupid == '196') {
                $res = $res->selectRaw('id,'.$this->fillable_group);
            }
        }
        
        /// search
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {

            if(strpos($req['search'], '=') !== false){
                $key_search = explode('=', $req['search']);
                $type = '=';
            }else if(strpos($req['search'], 'like') !== false){
                $key_search = explode('like', $req['search']);
                $type = 'like';
                $key_search[1] = '%'.$key_search[1].'%';
            }else if(strpos($req['search'], '<>') !== false){
                $key_search = explode('like', $req['search']);
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
            $c = explode(':', self::ORDERBY);
            $by = $c[0];
            $order = $c[1];
            $res = $res->orderBy($by, $order);
        }
        $delete = self::DELETE;
        return $res->where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
            })
        ->offset($from)
        ->limit($limit)
        ->paginate($limit)->appends(request()->query());
    }
    public function ShowOne($id)
    {
        $delete = self::DELETE;
        $groupid = auth::user()->groupid;
        if ($groupid == '196') {        
            return self::selectRaw('id,'.$this->fillable_group)->where(function($q) use ($delete) {
                            $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                        })->where(function($q) use ($id) {
                            $q->Where('ext_contact_id', $id)->orwhere('phone', $id);
                        })->first();  
        }
        if ($groupid == '103') {        
            return self::where(function($q) use ($delete) {
                            $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                        })->where(function($q) use ($id) {
                            $q->Where('id', $id)->orwhere('phone', $id);
                        })->first();  
        }
        return self::where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
            })->find($id);
    }
    static function checkContact($phone = '',$email = '',$id = '')
    {
        $delete = self::DELETE;
        $groupid = auth::user()->groupid;
        
        if ($groupid == '196') {
            $check_contact = self::where(function($q) use ($phone,$id) {
                    $q->Where('ext_contact_id', $id)->orwhere('phone', $phone)->orwhere('phone', $id);
                })->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->first();
        }else{
            if(!empty($email) && !empty($phone)){
                $check_contact = self::where(function($q) use ($phone, $email) {
                    $q->where('phone', $phone)->orWhere('email', $email);
                })->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->first();
            }elseif(empty($phone)){
                $check_contact = self::where('email',$email)->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->first();
            }else{
                $check_contact = self::where('phone',$phone)->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->first();
            }
        }
        return $check_contact;
    }

    static function customQuery($query,$arr=''){
        if(!empty($arr)){
            $res = DB::select($query);
            $res = array_map(function($item){
                return (array) $item;
            },$res);
        }else{
            $res = DB::select($query);
            $res = (array) reset($res);
        }       
        return $res;
    }
}

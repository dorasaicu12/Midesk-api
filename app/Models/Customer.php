<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Customer extends Model
{
    public $timestamps = false;
    protected $table = 'customer';

    protected $fillable = ['groupid','fullname','phone','email','address','province','creby','datecreate','channel'];
    
    const DELETED = 1;
    const DELETE = [NULL,0];
    const SORT = 'id';
    const ORDERBY = 'id:asc';
    const TAKE = 10;
    const FROM = 0;
    const KEYS = 'fullname';

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
        }
        /// search
        if (array_key_exists('key_search', $req) && rtrim($req['q']) != '') {
            $key_search = explode(',', $req['key_search']);
            foreach ($key_search as $key => $value) {                
                if (auth::user()->groupid == '2') {
                    $c = explode(':', $value);
                    $column = $c[0];
                    $condition = $c[1];
                    if (rtrim(strtolower($condition)) == 'and') {
                        $res->where($column,'like','%'.$req['q'].'%');
                    }elseif (rtrim(strtolower($condition)) == 'or') {
                        $res->orwhere($column,'like','%'.$req['q'].'%');
                    }
                }else{
                    $res = $res->where($value,'like','%'.$req['q'].'%');
                }
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
            $c = explode(':', self::ORDERBY);
            $by = $c[0];
            $order = $c[1];
            $res = $res->orderBy($by, $order);
        }
        $delete = self::DELETE;
        return $res->where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
        })->where('groupid',auth::user()->groupid)
        ->offset($from)
        ->limit($limit)
        ->paginate($limit)->appends(request()->query());
    }
    static function ShowOne($id)
    {
        $delete = self::DELETE;
        $groupid = auth::user()->groupid;
        return self::where(function($q) use ($delete) {
                $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
            })->where([['groupid',$groupid],['id',$id]])->first();
    }
}

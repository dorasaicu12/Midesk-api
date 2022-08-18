<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelsTraits;
use Auth;

class TicketCategory extends Model
{
    use ModelsTraits;
	protected $guarded = [];
	protected $table = 'ticket_category';
    public $timestamps = false;
    protected $fillable = [
                            'id',
                            'name',
                            'parent',
                            'parent2',
                            'level',
                        ];
                        const DELETED = 1;
                        const DELETE = [NULL,0];
                        const ORDERBY = 'id:asc';
                         const TAKE = 10;
                         const FROM = 0;                 

	public function Child()
	{
    	return $this->hasMany(self::class,'parent');
	}

    public function checkExist($id=''){
		return self::select($this->fillable)->where('groupid',auth::user()->groupid)->where('id',$id)->first();
	}

	public function getListDefault($req)
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
        /// search. 
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {

            if(strpos($req['search'], '<=>') !== false){
                $key_search = explode('<=>', $req['search']);
                $type = '=';
            }else if(strpos($req['search'], '<like>') !== false){
                $key_search = explode('<like>', $req['search']);
                $type = 'like';
                $key_search[1] = '%'.$key_search[1].'%';
            }else if(strpos($req['search'], '<>') !== false){
                $key_search = explode('like', $req['search']);
                $type = '<>'; // [] => "[]"
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
                
        })->where('groupid',auth::user()->groupid)
        ->offset($from)
        ->limit($limit)
        ->paginate($limit)->appends(request()->query());
    }
    public static function ShowOne($id)
    {
        $delete = self::DELETE;
        $groupid = auth::user()->groupid;
        return self::where(function($q) use ($delete) {
            })->find($id);
    }                   
}
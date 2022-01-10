<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
class Ticket extends Model
{
    public $timestamps = false;
    public $fillabled = false;
    protected $fillable = ['title','priority','status','category','assign_agent','assign_team','requester','groupid','createby','channel','requester_type','datecreate','is_delete','is_delete_date','is_delete_creby'];
	protected $table = 'ticket';
    const DELETED = 1;
    const DELETE = [NULL,0];
    const ORDERBY = 'id:asc';
    const FROM = 0;
    const TAKE = 10;
    const KEYS = 'title';
    function __construct()
    {
		$groupid = auth::user()->groupid;
        self::setTable('ticket_'.$groupid);
    }
    function getDefault($req)
    {
    	$res = self::with('getTicketsDetail');
    	
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
    		$res->selectRaw('id,'.$req['fields']);
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
    		$res->where($key_search[0],$type,$key_search[1]);
    	}
    	if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
    		$order_by = explode(',', $req['order_by']);
    		foreach ($order_by as $key => $value) {
    			$c = explode(':', $value);
    			$by = $c[0];
    			$order = $c[1];
    			$res->orderBy($by, $order);
    		}
    	}else{
    		$c = explode(':', self::ORDERBY);
			$by = $c[0];
			$order = $c[1];
			$res->orderBy($by, $order);
    	}
        $delete = self::DELETE;
    	return $res->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })
    	->offset($from)
    	->limit($limit)
    	->paginate($limit)->appends(request()->query());
    }
    public function getTicketsDetail()
    {
    	return $this->hasMany(TicketDetail::class,'ticket_id','id');
    }
    public function getTicketAssign()
    {
        return $this->hasOne(User::class,'id','assign_agent');
    }
	static function insert($table,$ins,$returnID=true){
		if($returnID) return DB::table($table)->insertGetId($ins);
		else return DB::table($table)->insert($ins);
	}
	static function show_order_where($table,$order_by,$where,$select="*"){
		$query = DB::table($table);
		if($select) $query->select(DB::raw($select));
		if(is_array($where)) $query->where($where);
		else $query->whereRaw($where);
		foreach ($order_by as $order) 
			$query->orderByRaw($order);

		$res = $query->get();
			
		$data = array();
		foreach ($res as $value) {
			$data[] = (array) $value;
		}
		return $data;
	}
    static function update_team_staff($agent_id,$team_id,$groupid){
        $sql = "UPDATE team_staff SET flag_facebook = (
                    CASE WHEN agent_id = ".$agent_id." THEN 1
                    ELSE 0 END
                ) 
                WHERE team_id = ".$team_id." AND groupid = ".$groupid;
        return DB::select($sql);
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
	static function show_by_id($table,$where,$select=''){
		$query = DB::table($table);
		if($select) $query->select(DB::raw($select));
		if(is_array($where)) $query->where($where);
		else $query->whereRaw($where);
		return (array) $query->limit(1)->first();
		// echo $query->toSql();		
	}
    static function showOne($id='')
    {
        $delete = self::DELETE;
        return self::with(['getTicketAssign'=> function ($q)
        {
            $q->select(['id','fullname']);
        },'getTicketsDetail.getTicketCreator' => function ($q)
        {
            $q->select(['id','fullname','picture',DB::raw("'https://dev2021.midesk.vn/upload/images/userthumb/' as path"),]);
        }])
        ->where('id',$id)
        ->where(function($q) use ($delete) {
            $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
        })->first();
    }
    public function checkExist_mtmb($orid='',$prcode='')
    {
        $delete = self::DELETE;
        return self::leftjoin('product',function ($join='') {
            $join->on(self::getTable().'.mt_productid','=','product.id');
        })->where('mt_orderid',$orid)->where('product.product_code',$prcode)
        ->where(function($q) use ($delete) {
            $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
        })->first([self::getTable().'.*']);
    }
}

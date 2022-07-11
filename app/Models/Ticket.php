<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use App\Traits\ModelsTraits;

class Ticket extends Model
{
    use ModelsTraits;

    public $timestamps = false;
    public $fillabled = false;
    protected $DELETE = [NULL,0];
    protected $fillable = [
                            'id',
                            'ticket_id',
                            'title',
                            'priority',
                            'status',
                            'category',
                            'assign_agent',
                            'assign_team',
                            'requester',
                            'groupid',
                            'createby',
                            'channel',
                            'requester_type',
                            'datecreate',
                            'is_delete',
                            'is_delete_date',
                            'is_delete_creby'
                        ];
                        
	protected $table = 'ticket';

    function __construct()
    {
		$groupid = auth::user()->groupid;
        self::setTable('ticket_'.$groupid);
    }
    public function getTicketsDetail()
    {
    	return $this->hasMany(TicketDetail::class,'ticket_id','id')->select((new TicketDetail)->getFillable());
    }
    public function getTicketAssign()
    {
        return $this->hasOne(User::class,'id','assign_agent');
    }
    public function getTicketTag()
    {
        return $this->hasOne(Tags::class,'id','tag');
    }
    public function getTicketLabel()
    {
        return $this->hasOne(TicketLabel::class,'id','label');
    }
    public function getTicketAssignTeam()
    {
        return $this->hasOne(Team::class,'team_id','assign_team');
    }
    public function getTicketPriority()
    {
        return $this->hasOne(TicketPriority::class,'id','priority');
    }
    public function getTicketCategory()
    {
        return $this->hasOne(TicketCategory::class,'id','category');
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
    public function showOne($id='')
    {
        $delete = $this->DELETE;
        $ticket = self::select($this->fillable)->with(['getTicketAssign'=> function ($q)
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
        if (!$ticket) {
            return [];
        }
        switch ($ticket->requester_type) {
            case 'contact':
                $ticket->requester_info = Contact::select('fullname','phone')->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->find($ticket->requester);
                break;
            case 'agent':
                $ticket->requester_info = User::select('fullname','phone')->find($ticket->requester);
                break;
            case 'customer':
                $ticket->requester_info = Customer::select('fullname','phone')->where(function($q) use ($delete) {
                    $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
                })->find($ticket->requester);
                break;
        }
        return $ticket;
    }
    public function checkExist_mtmb($orid='',$prcode='')
    {
        $delete = $this->DELETE;
        return self::leftjoin('product',function ($join='') {
            $join->on(self::getTable().'.mt_productid','=','product.id');
        })->where('mt_orderid',$orid)->where('product.product_code',$prcode)
        ->where(function($q) use ($delete) {
            $q->where('is_delete', $delete[0])->orWhere('is_delete', $delete[1]);
        })->first([self::getTable().'.*']);
    }
}

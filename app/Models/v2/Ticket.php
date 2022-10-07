<?php

namespace App\Models\v2;

use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use App\Traits\ModelsTraits;
use App\Libraries\Encryption;
class Ticket extends Model
{
    use ModelsTraits;

    public $timestamps = false;
    public $fillabled = false;
    protected $DELETE = [NULL,0];
    const DELETED = 1;
    const DELETE = [NULL,0];
    const SORT = 'id';
    const ORDERBY = 'id:asc';
    const TAKE = 10;
    const FROM = 0;
    protected $fillable ='id,ticket_id,title,event_id,priority,status,assign_agent,assign_team,category,type,priority,tag,label,channel,tag,
    label,
    label_creby,requester,
    requester_type,datecreate,dateupdate';
                        
                        
	protected $table = 'ticket';

    function __construct()
    {
		$groupid = auth::user()->groupid;
        self::setTable('ticket_'.$groupid);
    }
    function getDefault($req)
    {
        $res =  self::with(['getTicketsDetail','getTicketContact','getTicketPriority']);
    	
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
                $res = $res->selectRaw('id,'.$this->fillable);
            }
            $res = $res->selectRaw('id,'.$this->fillable);
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
                $key_search = explode('<>', $req['search']);
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
    	return $this->hasMany(TicketDetail::class,'ticket_id','id')->select((new TicketDetail)->getFillable());
    }
    public function getLastedTicketsDetail()
    {
    	return $this->hasMany(TicketDetail::class,'ticket_id','id')->limit(1)->od;
    }

    public function getTicketsContent()
    {
    	return $this->hasMany(TicketDetail::class,'ticket_id','id')->select((new TicketDetail)->getFillable());
    }

    public function getTicketsComment()
    {
    	return $this->hasMany(TicketDetail::class,'ticket_id','id')->select((new TicketDetail)->getFillable())->orderBy('datecreate','desc')->limit(1);
    }
    public function getTicketsEvent()
    {
    	return $this->hasMany(Event::class,'ticket_id','id');
    }
    public function getTicketAssign()
    {
        return $this->hasOne(User::class,'id','assign_agent');
    }
    public function getTicketTag()
    {
        return $this->hasMany(Tags::class,'id','tag');
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
    public function getTicketContact()
    {
        return $this->hasOne(Contact::class,'id','requester')->select((new Contact)->getFillable());
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
    public  function showOne($id='')
    {
        $delete = $this->DELETE;
        $ticket = self::selectRaw($this->fillable)->with(['getTicketAssign'=> function ($q)
        {
            $q->select(['id','fullname']);
        },
        'getTicketLabel'=> function ($q)
        {
            $q->select(['id','name']);
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

    public function showTicketDetail($id){
        $ticket_detail=TicketDetail::where('ticket_id',$id)->get();
        $detail_infor=[];
        foreach($ticket_detail as $value){
            $creator='';
            $get_creator=User::where('id',$value['createby'])->get();
            if(isset($get_creator)){
                foreach($get_creator as $user){
                    if($user->picture ==""){
                        $path='https://dev2022.midesk.vn/upload/images/userthumb/'.'no_user_photo-v1.jpg';
                    }else{
                        $path='https://dev2022.midesk.vn/upload/images/userthumb/'.$user->picture;
                    }
                    $creator=[
                        'id'=>$user['id'],
                        'fullname'=>$user['fullname'],
                        'path'=>$path,
                        'level'=>$user['level'],
                    ];
                } 
            }
            if($value['type']=='text'){

                if($value['is_delete']== 1){
                    $value['content_true']='<i style="color: #e23d3d;">This message has been removed.</i>';
                }else{
                    if($value['content']!== null && $value['content_system']!== null){
                        $value['content_true']=$value['content_system'].' '.$value['content'];
                    }elseif($value['content_system']== null){
                        $value['content_true']=$value['content'];
                    }elseif($value['content']== null){
                        $value['content_true']=$value['content_system'];
                    }
                }
                $detail_infor[]=[
                    'id'=>$value['id'],
                    'content'=>$value['content_true'],
                    'type'=>$value['type'],
                    'private'=>$value['private'],
                    "attaments"=>[],
                    'get_tickets_creator'=>$creator
                ];
                
            }elseif($value['type']=='file'){
                    $encryption=new Encryption;
                   $encryption->initialize(array('cipher' => 'aes-256','mode' => 'ctr','key' => "MITEK@2016"));
                if($value['is_delete']== 1){
                    $value['content_true']='<i style="color: #e23d3d;">This message has been removed.</i>';
                }else{
                    if($value['content']!== null && $value['content_system']!== null){
                        $value['content_true']=$value['content_system'].' '.$value['content'];
                    }elseif($value['content_system']== null){
                        $value['content_true']=$value['content'];
                    }elseif($value['content']== null){
                        $value['content_true']=$value['content_system'];
                    }
                }  
                if(isset($value['file_multiple'])){
                    $array=json_decode($value['file_multiple'],true);
                    $items = array();
                    foreach($array as $fileArray){
                        $token = array(
                            "id"         => auth::user()->id, //id user,
                            "groupid"    => auth::user()->groupid, //groupid
                            "filename"   => $fileArray['file_name'], //file_original,
                            "datecreate" => $value['datecreate'], //ngày tạo ticket_detial | ngày tạo file
                            "time"       => time(), //thời gian gọi Api
                        );
                           $data =  base64_encode($encryption->encrypt(json_encode($token)));
                        // echo  $data;
                     //      echo $encryption->decrypt(base64_decode(('NDA4OTIwMDFhNTQ5MjNiNTkxYTkyNThhODdmZjZkNzgxZThjNTk5NGE4ZTBkYWEyNzMyM2E0NWYxZTJkNGY2ZjQ2M2M5MzBlNmY2YzE0ZjUxMzQwY2JjZjM2ZDBmYjJmYzExYmU2YzY2YWQ3MzdlOTcxNjg1MjVhYTNkZmJhZWVHME5obDJZcUkrVnhaWWJwUXh0WnRQRVZSU1JTc2dSM0RmUHhYVldmSmpmNUhseEFVNFhyZk44L0tKZkszTEU0V0trYnhKeVpWMkhxYzBHTjlQRWhHb09UWEE0ZzRUdzVJRVpMbW5OWVRCTzE4Wis3OTR2TnhuWnpxd092Y2dIK3QvU2pOc3d0MG5nNmhBYWh1SlNw')));
                        //    exit;   
                        $fileArray['link']  ="https://dev2022.midesk.vn/file-data/".$data;
                        $items[] = $fileArray;
                    }
                    $detail_infor[]=[
                        'id'=>$value['id'],
                        'content'=>$value['content_true'],
                        'type'=>$value['type'],
                        'private'=>$value['private'],
                        "attaments"=>$items,
                        'get_tickets_creator'=>$creator,
                    ];
                }else{
                    $token = array(
                        "id"         => auth::user()->id, //id user,
                        "groupid"    => auth::user()->groupid, //groupid
                        "filename"   => $value['file_name'], //file_original,
                        "datecreate" => $value['datecreate'], //ngày tạo ticket_detial | ngày tạo file
                        "time"       => time(), //thời gian gọi Api
                    );
                    $data =  base64_encode($encryption->encrypt(json_encode($token)));
                    $file=[
                        'file_size'=>$value['file_size'],
                        'file_extension'=>$value['file_extension'],
                        'file_original'=>$value['file_original'],
                        'file_name'=>$value['file_name'],
                        'link'=>"https://dev2022.midesk.vn/file-data/".$data
                    ];
                    $detail_infor[]=[
                        'id'=>$value['id'],
                        'content'=>$value['content_true'],
                        'type'=>$value['type'],
                        'private'=>$value['private'],
                        "attaments"=>[$file],
                        'get_tickets_creator'=>$creator
                    ];
                }
            }
        }
        return $detail_infor;
    }
    public function GetCategory($id){
        $category=TicketCategory::where()->first($id);
    }
}
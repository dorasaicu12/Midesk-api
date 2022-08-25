<?php

namespace App\Models\v2;

use Illuminate\Database\Eloquent\Model;
use Auth;

class TicketDetail extends Model
{
	public $timestamps = false;
	
    protected $table = 'ticket_detail';
    protected $fillable = ['id','ticket_id','title','content','createby'];
    // protected $fillable = ['ticket_id','title','content','groupid','type','createby','createby_level','datecreate','status','private'];
    function __construct()
    {
        $groupid = auth::user()->groupid;
        self::setTable('ticket_detail_'.$groupid);
    }
    public function getTicketCreator()
    {
    	return $this->hasOne(User::class,'id','createby');
    }
}
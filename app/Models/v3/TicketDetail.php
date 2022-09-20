<?php

namespace App\Models\v3;

use Illuminate\Database\Eloquent\Model;
use Auth;

class TicketDetail extends Model
{
	public $timestamps = false;
	
    protected $table = 'ticket_detail';
    protected $fillable = ['id','ticket_id','title','content','file_size','file_extension','file_name','file_original','datecreate','createby'];
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
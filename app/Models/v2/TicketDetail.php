<?php

namespace App\Models\v2;

use Auth;
use Illuminate\Database\Eloquent\Model;

class TicketDetail extends Model
{
    public $timestamps = false;

    protected $table = 'ticket_detail';
    protected $fillable = ['id', 'ticket_id', 'title', 'content', 'content_system', 'type', 'file_size', 'file_extension', 'file_name', 'file_original', 'file_multiple', 'datecreate', 'createby'];
    // protected $fillable = ['ticket_id','title','content','groupid','type','createby','createby_level','datecreate','status','private'];
    public function __construct()
    {
        $groupid = auth::user()->groupid;
        self::setTable('ticket_detail_' . $groupid);
    }
    public function getTicketCreator()
    {
        return $this->hasOne(User::class, 'id', 'createby');
    }
}
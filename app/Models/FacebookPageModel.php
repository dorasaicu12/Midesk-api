<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookPageModel extends Model
{
    
    public $timestamps = false;
    public $fillabled = false;
    protected $DELETE = [NULL,0];
    const DELETED = 1;
    const DELETE = [NULL,0];
    const SORT = 'id';
    const ORDERBY = 'id:asc';
    const TAKE = 10;
    const FROM = 0;                 
	protected $table = 'facebook_api';
}
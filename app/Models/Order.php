<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Order extends Model
{
    public $fillabled = false;
	protected $table = 'order';

	public function checkExist($ord=''){
		return self::where('groupid',auth::user()->groupid)->where('ord_code',$ord)->first();
	}
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
	protected $guarded = [];
	protected $table = 'product';

    static function checkProductByCode($code,$groupid)
    {
        return self::where('product_code',$code)->where('groupid',$groupid)->first();
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Traits\ModelsTraits;

class Order extends Model
{
    use ModelsTraits;
    
    protected $guarded = [];
	protected $table = 'order';
    protected $fillable = [
    						'id',
    						'ord_code',
    						'ord_address',
    						'ord_description',
    						'ord_group_name',
    						'ord_group',
    						'ord_status_value',
    						'ord_status',
    						'ord_discount',
    						'ord_surcharge',
    						'ord_total',
    						'ord_rest_of_total',
    						'ord_ship',
    						'ord_customer_id',
    						'ord_customer_name'
    					];

	public function checkExist($ord=''){
		return self::select($this->fillable)->where('groupid',auth::user()->groupid)->where('ord_code',$ord)->first();
	}

}

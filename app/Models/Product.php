<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelsTraits;

class Product extends Model
{
	use ModelsTraits;
	
	protected $guarded = [];
	protected $table = 'product';
	protected $fillable = [
							'id',
							'branch_id',
							'product_code',
							'product_category_id',
							'product_is_active',
							'product_name',
							'product_full_name',
							'product_orig_price',
							'product_price',
							'product_unit',
							'product_unit_id',
							'product_type',
							'product_weight',
							'product_stock',
							'product_unlimited',
							'product_allows_sale',
							'product_week_expire',
							'product_day_expire',
							'is_surcharge',
							'product_barcode',
							'product_origin',
							'created_at',
							'groupid',
							'channel',
							'created_by',
							'updated_by'
						];

    static function checkProductByCode($code,$groupid)
    {
        return self::where('product_code',$code)->where('groupid',$groupid)->first();
    }

}

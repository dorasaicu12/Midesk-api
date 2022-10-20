<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $guarded = [];

    public $timestamps = false;
    protected $fillable = [
        'groupid',
        'order_id',
        'product_id',
        'channel',
        'quantity',
        'weight',
        'price',
        'discount',
        'product_desc',
        'product_name',
        'product_code',
        'sub_total',
    ];
    protected $table = 'order_detail';

    public function Products()
    {
        return $this->hasMany(Product::class, 'id', 'product_id');
    }

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldValue extends Model
{
    protected $table = 'field_custom_value';

    protected $fillable = ['field_id','type_id','type','value'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $fillable = ['title','name','field_type','placeholder','datecreate','groupid'];

    protected $table = 'field_custom';
    
    public $timestamps = false;
}

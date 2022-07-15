<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class actionLog extends Model
{
    protected $table = 'action_logs';
    protected $fillable = ['groupid','created_by','title','content','detail'];

}

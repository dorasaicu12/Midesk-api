<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Macro extends Model
{
    
    protected $guarded = [];
	protected $table = 'macro';
    protected $fillable = [
                            'id',
                            'title',
                            'description',
                            'type',
                            'type_id',
                            'action',
                        ];

}

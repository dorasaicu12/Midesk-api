<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MarcoModel extends Model
{
    protected $table = 'macro';
    protected $casts = [
        'action' => 'array'
    ];
}

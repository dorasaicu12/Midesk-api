<?php

namespace App\Models;

use App\Traits\ModelsTraits;
use Auth;
use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    use ModelsTraits;
    protected $table = 'event_type';
    protected $fillable = [
        'id',
        'etype_name',
    ];
    public function getAll()
    {
        return self::select($this->fillable)->where('groupid', auth::user()->groupid)->get()->toArray();
    }

}

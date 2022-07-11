<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Traits\ModelsTraits;

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
		return self::select($this->fillable)->where('groupid',auth::user()->groupid)->get()->toArray();
    }

}

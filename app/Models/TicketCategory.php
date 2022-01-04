<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
	protected $guarded = [];
	protected $table = 'ticket_category';

	public function Child()
	{
    	return $this->hasMany(self::class,'parent');
	}
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelsTraits;

class TicketCategory extends Model
{
    use ModelsTraits;
	protected $guarded = [];
	protected $table = 'ticket_category';
    protected $fillable = [
                            'id',
                            'name',
                            'parent',
                            'parent2',
                            'level',
                        ];

	public function Child()
	{
    	return $this->hasMany(self::class,'parent');
	}
}

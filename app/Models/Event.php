<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Traits\ModelsTraits;

class Event extends Model
{	
    use ModelsTraits;
    protected $table = 'event';
    protected $fillable = [
    						'id',
    						'event_title',
    						'event_source',
    						'event_source_id',
    						'customer_id',
    						'contact_id',
    						'order_id',
    						'ticket_id',
    						'event_status',
    						'remind_time',
    						'remind_type',
    						'event_type',
    						'event_type_name',
    						'days_noti_before',
    						'days_noti_before_type',
    						'days_noti_before_agent',
    					];

}

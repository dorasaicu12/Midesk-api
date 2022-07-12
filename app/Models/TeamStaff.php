<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamStaff extends Model
{
	protected $guarded = [];
	protected $table = 'team_staff';

	public function Agent()
	{
		return $this->hasOne(User::class,'id','agent_id');
	}
	public function Team()
	{
		return $this->hasOne(Team::class,'team_id','team_id');
	}
}

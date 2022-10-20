<?php

namespace App\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

class TeamStaff extends Model
{
    protected $guarded = [];
    protected $table = 'team_staff';

    public function Agent()
    {
        return $this->hasOne(User::class, 'id', 'agent_id');
    }
    public function Team()
    {
        return $this->hasOne(Team::class, 'team_id', 'team_id');
    }

    public function getTeamInfor($id)
    {
        $team = self::where('agent_id', $id)->get();
        $team_id = '';
        $team_infor = array();
        if ($team != '') {
            foreach ($team as $k => $val) {
                $team_id = $val['team_id'];
                if ($team_id == '') {
                    $team_infor = [];
                } else {
                    $team_depart = Team::where('team_id', $team_id)->get();
                    foreach ($team_depart as $k2 => $val2) {
                        $team_infor[] = $val2['team_name'];
                    }
                }
            }

        } else {
            $team_infor = [];
        }

        return $team_infor;
    }
}
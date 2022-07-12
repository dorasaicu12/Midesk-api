<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Contact;
use App\Models\EventType;
use App\Http\Functions\MyHelper;
use App\Http\Requests\EventRequest;
use App\Models\Team;
use App\Models\User;
use App\Models\Customer;
use App\Models\TeamStaff;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $array_remind_type = ['date','daily','weekly','monthly','yearly'];
    protected $array_event_source = ['agent','contact','customer'];
    
    public function index(Request $request)
    {
        $req = $request->all();
        $events = new Event;
        $events = $events->getDefault($req);
        return MyHelper::response(true,'Successfully',$events,200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $title = $request->event_title;
        $event_type = $request->event_type;
        $check_event_type = (new EventType)->find($event_type);
        if (!$check_event_type) {
            return MyHelper::response(false,'Event type field do not exist', [],403);
        }
        $note = $request->note;
        $event_location = $request->event_location;
        if (strlen($request->remind_time) == 10) {
            $request->remind_time = $request->remind_time . '00:00:00';
        }
        $remind_time = $request->remind_time;
        $remind_type = $request->remind_type;
         
        if (!in_array($remind_type, $this->array_remind_type)) {
            return MyHelper::response(false,'remind_type field do not match', [],403);
        }

        $team_id = $request->handling_team;
        $check_team = (new Team)->where('team_id',$team_id)->first();
        if (!$check_team) {
            return MyHelper::response(false,'handling_team field do not match', [],403);
        }

        $agent_id = $request->handling_agent;
        $check_agent = (new TeamStaff)->where('team_id',$check_team->team_id)->get()->pluck('agent_id')->toArray();
        if (!in_array($agent_id, $check_agent)) {
            return MyHelper::response(false,'handling_agent field do not match', [],403);
        }

        $event_source = $request->event_source;
        $event_source_id = $request->event_source_id;
        if (!in_array($event_source, $this->array_event_source)) {
            return MyHelper::response(false,'Event source field do not match', [],403);
        }

        if ($event_source == 'contact') {
            $contact = (new Contact)->find($event_source_id);
            if (!$contact) {
                return MyHelper::response(false,'Event source do not exist', [],403);
            }
        }elseif ($event_source == 'agent') {
            $agent = (new User)->find($event_source_id);
            if (!$agent) {
                return MyHelper::response(false,'Event source do not exist', [],403);
            }
        }else{
            $customer = (new Customer)->find($event_source_id);
            if (!$customer) {
                return MyHelper::response(false,'Event source do not exist', [],403);
            }
        }

        $event = new Event;
        $event->event_title = $title;
        $event->event_type = $event_type;
        $event->note = $note;
        $event->event_location = $event_location;
        $event->remind_time = $remind_time;
        $event->remind_type = $remind_type;
        $event->event_assign_team = $team_id;
        $event->event_assign_agent = $agent_id;
        $event->event_source_id = $event_source_id;
        $event->event_source = $event_source;
        $event->groupid = auth()->user()->groupid;
        $event->created_by = auth()->user()->id;
        
        if ($event->save()) {
            return MyHelper::response(true,'Create event successfully', ['id' => $event->id],200);
        }else{
            return MyHelper::response(false,'Create event failed', [],403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $event = Event::where('id', $id)->first();
        if (!$event) {            
            return MyHelper::response(false,'Event not found',[],404);
        }else{
            return MyHelper::response(true,'Successfully',$event,200);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $event = Event::where('id', $id)->first();
        if (!$event) {
            return MyHelper::response(false,'Event do not exist', [],404);
        }
        $request = array_filter($request->all()); 

        if (array_key_exists('remind_time', $request)) {

        	$request['remind_time'] = $request['remind_time'];
	    }

        if (array_key_exists('remind_type', $request)) {
        	if (!in_array($request['remind_type'], $this->array_remind_type)) {
	            return MyHelper::response(false,'remind_type field do not match', [],403);
        	}
        }

        if (array_key_exists('handling_team', $request)) {
        	$check_team = (new Team)->where('team_id',$request['handling_team'])->first();
        	if ($check_team) {
        		$request['event_assign_team'] = $request['handling_team'];
        	}else{
	            return MyHelper::response(false,'handling_team field do not match', [],403);
        	}
        	if (array_key_exists('handling_agent', $request)) {
	        	$check_agent = (new TeamStaff)->where('team_id',$check_team->team_id)->get()->pluck('agent_id')->toArray();
	        	if ($check_agent) {
	        		$request['event_assign_agent'] = $request['handling_agent'];
	        	}else{
		            return MyHelper::response(false,'handling_agent field do not match', [],403);
	        	}
        	}else{
	            return MyHelper::response(false,'handling_agent field is require', [],403);
        	}
        }

       	if (array_key_exists('event_source', $request) && array_key_exists('event_source_id', $request)) {
       		if (!in_array($request['event_source'], $this->array_event_source)) {
            	return MyHelper::response(false,'Event source field do not match', [],403);
       		}else{
       			if ($request['event_source'] == 'contact') {
		            $contact = (new Contact)->find($request['event_source_id']);
		            if (!$contact) {
		                return MyHelper::response(false,'Event source do not exist', [],403);
		            }
		        }elseif ($request['event_source'] == 'agent') {
		            $agent = (new User)->find($request['event_source_id']);
		            if (!$agent) {
		                return MyHelper::response(false,'Event source do not exist', [],403);
		            }
		        }else{
		            $customer = (new Customer)->find($request['event_source_id']);
		            if (!$customer) {
		                return MyHelper::response(false,'Event source do not exist', [],403);
		            }
		        }
       		}
       	}

       	if (array_key_exists('event_type', $request)) {
       		$check_event_type = (new EventType)->find($request['event_type']);
	        if (!$check_event_type) {
	            return MyHelper::response(false,'Event type field do not exist', [],403);
	        }	
       	}
        if ($event->update($request)) {
        	return MyHelper::response(true,'Update event successfully', [],200);
        }else{
            return MyHelper::response(false,'Update event failed', [],403);
        }

    }

    public function eventForm()
    {
        $groupid = auth()->user()->groupid;
        $data = [];
        $data['category'] = (new EventType)->getAll();
        $data['type_remind'] = $this->array_remind_type; 
        $data['team'] = Team::select('team_id','team_name')->where('groupid',$groupid)->get();
        $data['agent'] = TeamStaff::select('team_id','agent_id')->with(['Agent' => function ($q){
            $q->select('id','fullname');
        }])->where('groupid',$groupid)->get();
        return MyHelper::response(true,'Create event successfully', $data,200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $event = Event::where('id', $id)->first();
        if (!$event) {            
            return MyHelper::response(false,'Event not found',[],404);
        }else{
            $eventact = Event::find($id);
            $eventact->delete();
            return MyHelper::response(true,'Delete Event Successfully', [],200);
        }
    }
}

<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Http\Functions\MyHelper;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
        $req = $request->all();
        $events = new Event;
        $event_title=$req['event_title'];
        $event_type=$req['event_type'];
        $note=$req['note'];
        $event_location=$req['event_location'];
        $remind_time=$req['remind_time'];
        $remind_type=$req['remind_type'];
        $handling_team=$req['handling_team'];
        $handling_agent=$req['handling_agent'];
        $event_source=$req['event_source'];
        $event_source_id=$req['event_source_id'];

        $events->event_title     	= $event_title;
        $events->event_type   	    = $event_type;
        $events->note   	        = $note;
        $events->event_location     = $event_location;
        $events->remind_time      	= $remind_time ;                  
        $events->remind_type     	= $remind_type ;                  
        $events->event_assign_team    	= $handling_team;
        $events->event_assign_agent 	= $handling_agent;
        $events->event_source     	= $event_source;
        $events->event_source_id    = $event_source_id;
        $events->save();
        $id = $events->id;


        usleep(1000);

        $new_event = Event::select('id')->find($id);                

        return MyHelper::response(true,'Create contact successfully',[$new_event],201);
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
        //
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
            return MyHelper::response(false,'Ticket not found',[],404);
        }else{
            $eventact = Event::find($id);
            $eventact->delete();
            return MyHelper::response(true,'Delete Ticket Successfully', [],200);
        }
    }
}

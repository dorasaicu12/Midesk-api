<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Order;

use App\Models\Contact;
use App\Models\EventType;

use App\Http\Functions\MyHelper;
use App\Http\Functions\CheckField;

use App\Http\Requests\EventRequest;
use App\Models\Team;
use App\Models\User;
use App\Models\Customer;
use App\Models\TeamStaff;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
class EventController extends Controller
{
    protected $array_remind_type = ['date','daily','weekly','monthly','yearly'];
    protected $array_event_source = ['agent','contact','customer'];
    /**
    * @OA\Get(
    *     path="/api/v3/event",
    *     tags={"Event"},
    *     summary="Get list event",
    *     description="<h2>This API will Get list event with condition below</h2>",
    *     operationId="index",
    *     @OA\Parameter(
    *         name="page",
    *         in="query",
    *         required=false,
    *         explode=true,
    *         example=1,
    *         description="<h4>Number of page to get</h4>
                    <code>Type: <b id='require'>Number</b></code>"
    *     ),
    *     @OA\Parameter(
    *         name="limit",
    *         in="query",
    *         required=false,
    *         explode=true,
    *         example=5,
    *         description="<h4>Total number of records to get</h4>
                    <code>Type: <b id='require'>Number<b></code>"
    *     ),
    *     @OA\Parameter(
    *         name="search",
    *         in="query",
    *         example="event_title<=>Gọi điện hỏi thăm",
    *         description="<h4>Find records with condition get result desire</h4>
                    <code>Type: <b id='require'>String<b></code><br>
                    <code>Seach type supported with <b id='require'><(like,=,!=,beetwen)></b> </code><br>
                    <code>With type search beetwen value like this <b id='require'> created_at<<beetwen>beetwen>{$start_date}|{$end_date}</b> format (Y/m/d H:i:s)</code><br>
                    <code id='require'>If multiple search with connect (,) before</code>",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Parameter(
    *         name="order_by",
    *         in="query",
    *         example="id:DESC",
    *         description="<h4>Sort records by colunm</h4>
                    <code>Type: <b id='require'>String</b></code><br>
                    <code>Sort type supported with <b id='require'>(DESC,ASC)</b></code><br>
                    <code id='require'>If multiple order with connect (,) before</code>",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Parameter(
    *         name="fields",
    *         in="query",
    *         required=false,
    *         explode=true,
    *         example="event_title,event_source,remind_time,remind_type",
    *         description="<h4>Get only the desired columns</h4>
                    <code>Type: <b id='require'>String<b></code>"
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="status", type="boolean", example="true"),
    *             @OA\Property(property="message", type="string", example="Successfully"),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="data",type="object",
    *                   @OA\Property(property="id",type="string", example="1"),
    *                   @OA\Property(property="firstname",type="string", example="văn A"),
    *                   @OA\Property(property="lastname",type="string", example="Nguyễn"),
    *                   @OA\Property(property="phone",type="string", example="0987654321"),
    *                   @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
    *                 ),
    *                 @OA\Property(property="current_page",type="string", example="1"),
    *                 @OA\Property(property="first_page_url",type="string", example="null"),
    *                 @OA\Property(property="next_page_url",type="string", example="null"),
    *                 @OA\Property(property="last_page_url",type="string", example="null"),
    *                 @OA\Property(property="prev_page_url",type="string", example="null"),
    *                 @OA\Property(property="from",type="string", example="1"),
    *                 @OA\Property(property="to",type="string", example="1"),
    *                 @OA\Property(property="total",type="string", example="1"),
    *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/event"),
    *                 @OA\Property(property="last_page",type="string", example="null"),
    *             )
    *         )
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     }
    * )
    */
    
 
    
    
    public function index(Request $request)
    {
        $req = $request->all();
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $checkFileds= CheckField::check_fields($req,'event');
             if($checkFileds){
                Log::channel('event_history')->info('retrive Event falid by error '.$checkFileds.'',['status' => 404,'request'=>$req]);
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds= CheckField::check_order($req,'event');
             if($checkFileds){
                Log::channel('event_history')->info('retrive Event falid by error '.$checkFileds.'',['status' => 404,'request'=>$req]);
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds= CheckField::CheckSearch($req,'event');
             if($checkFileds){
                Log::channel('event_history')->info('retrive Event falid by error '.$checkFileds.'',['status' => 404,'request'=>$req]);
                return MyHelper::response(false,$checkFileds,[],404);
             }
             $checksearch= CheckField::check_exist_of_value($req,'event');

             if($checksearch){
                Log::channel('event_history')->info('retrive Event falid by error '.$checksearch.'',['status' => 404,'request'=>$req]);
                return MyHelper::response(false,$checksearch,[],404);
             }
           } 
           
        $events = (new Event)->getListDefault($req);
        Log::channel('event_history')->info('Retrive data successfully',['status' => 200,'request'=>$req]);
        return MyHelper::response(true,'Successfully',$events,200);
    }
    
    /**
    * @OA\Get(
    *     path="/api/v3/event/eventForm",
    *     tags={"Event"},
    *     summary="Get sample data to create event",
    *     description="<h2>This API will get sample data to create event</h2><br><code>Press try it out button to modified</code>",
    *     operationId="eventForm",
    *     @OA\Response(
    *         response=200,
    *         description="Successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="status", type="boolean", example="true"),
    *             @OA\Property(property="message", type="string", example="Successfully"),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="data",type="object",
    *                   @OA\Property(property="team",type="object",
    *                     @OA\Property(property="13",type="array", 
    *                       @OA\Items(type="object",
    *                         @OA\Property(property="team_id",type="string", example="1"),
    *                         @OA\Property(property="team_name",type="string", example="team 1"),
    *                       ),
    *                       @OA\Items(type="object",
    *                         @OA\Property(property="team_id",type="string", example="1"),
    *                         @OA\Property(property="team_name",type="string", example="team 2"),
    *                       ),
    *                     ),
    *                   ),
    *                   @OA\Property(property="agent",type="array",
    *                     @OA\Items(type="object",
    *                       @OA\Property(property="team_id",type="string", example="21"),
    *                       @OA\Property(property="agent_id",type="string", example="11"),
    *                       @OA\Property(property="agent",type="array",
    *                         @OA\Items(type="object",
    *                           @OA\Property(property="id",type="string", example="1"),
    *                           @OA\Property(property="fullname",type="string", example="agent name"),
    *                         ),
    *                       ),
    *                     ),
    *                   ),
    *                   @OA\Property(property="category",type="array",
    *                     @OA\Items(type="object",
    *                       @OA\Property(property="id",type="string", example="1"),
    *                       @OA\Property(property="etype_name",type="string", example="Dinner"),
    *                     ),
    *                     @OA\Items(type="object",
    *                       @OA\Property(property="id",type="string", example="2"),
    *                       @OA\Property(property="etype_name",type="string", example="Meeting"),
    *                     ),
    *                   ),
    *                   @OA\Property(property="type_remind",type="array",
    *                     @OA\Items(type="string",example="date"),
    *                     @OA\Items(type="string",example="daily"),
    *                   ),
    *                 ),
    *             )
    *         )
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     }
    * )
    */
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
        Log::channel('event_history')->info('Create event successfully',['status' => 200,'data'=>$data]);
        return MyHelper::response(true,'Create event successfully', $data,200);
    }

    
    /**
    * @OA\POST(
    *     path="/api/v3/event",
    *     tags={"Event"},
    *     summary="Create a event",
    *     description="<h2>This API will Create a event with json form below</h2><br><code>Press try it out button to modified</code>",
    *     operationId="store",
    *     @OA\RequestBody(
    *       required=true,
    *       description="<table id='my-custom-table'>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <td><b id='require'>Required</b></td>
                </tr>
                <tr>
                    <th>event_title</th>
                    <td>Title of ticket</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>event_type</th>
                    <td>Category of event</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>Note</th>
                    <td>Note of event</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>event_location</th>
                    <td>event venue</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>remind_time</th>
                    <td>time to remind</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>remind_type</th>
                    <td>type remind of event</td>
                    <td>true ('date','daily','weekly','monthly','yearly')</td>
                </tr>
                <tr>
                    <th>handling_team</th>
                    <td>handling parts</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>handling_agent</th>
                    <td>agent handling this event</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>event_source</th>
                    <td>source event</td>
                    <td>true (contact,agent,customer)</td>
                </tr>
                <tr>
                    <th>event_source_id</th>
                    <td>id of source event</td>
                    <td>true</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       @OA\JsonContent(
    *         @OA\Property(property="event_title", type="string", example="Sinh nhật"),
    *         @OA\Property(property="event_type", type="string", example="1"),
    *         @OA\Property(property="note", type="string", example="this is example"),
    *         @OA\Property(property="remind_time", type="string", example="15/03/2022 17:20:00"),
    *         @OA\Property(property="remind_type", type="string", example="daily"),
    *         @OA\Property(property="handling_team", type="number", example="1"),
    *         @OA\Property(property="handling_agent", type="number", example="1"),
    *         @OA\Property(property="event_source", type="string", example="1"),
    *         @OA\Property(property="event_source_id", type="number", example="1"),
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Create Event Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="true"),
    *           @OA\Property(property="message", type="string", example="Create Event Successfully"),
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="string", example="1"),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=422,
    *         description="Create Event failed",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="true"),
    *           @OA\Property(property="message", type="string", example="The given data was invalid"),
    *           @OA\Property(property="errors",type="object",
    *             @OA\Property(property="event_title",type="array", 
    *               @OA\Items(type="string", example="the event title field is required")
    *             ),
    *           )
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function store(EventRequest $request)
    {

        $title = $request->event_title;
        $event_type = $request->event_type;
        $check_event_type = (new EventType)->find($event_type);
        if (!$check_event_type) {
            Log::channel('event_history')->info('Event type field do not exist',['status' => 403,'request'=>$request]);
            return MyHelper::response(false,'Event type field do not exist', [],403);
        }
        $note = $request->note;
        $event_location = $request->event_location;
        if (strlen($request->remind_time) == 10) {
            $request->remind_time = $request->remind_time . '00:00:00';
        }
        $remind_time = Carbon::parse( $request->remind_time)->format('Y-m-d H:i:s');
        $remind_type = $request->remind_type;

        if (!in_array($remind_type, $this->array_remind_type)) {
            Log::channel('event_history')->info('remind_type field do not match',['status' => 403,'request'=>$request]);
            return MyHelper::response(false,'remind_type field do not match', [],403);
        }

        $team_id = $request->handling_team;
        $check_team = (new Team)->where('team_id',$team_id)->first();
        if (!$check_team) {
            Log::channel('event_history')->info('handling_team field do not match',['status' => 403,'request'=>$request]);
            return MyHelper::response(false,'handling_team field do not match', [],403);
        }

        $agent_id = $request->handling_agent;
        $check_agent = (new TeamStaff)->where('team_id',$check_team->team_id)->get()->pluck('agent_id')->toArray();
        if (!in_array($agent_id,$check_agent)) {
            Log::channel('event_history')->info('handling_team field do not match',['status' => 403,'request'=>$request]);
            return MyHelper::response(false,'handling_agent field do not match', [],403);
        }

        $event_source = $request->event_source;
        $event_source_id = $request->event_source_id;
        if (!in_array($event_source, $this->array_event_source)) {
            Log::channel('event_history')->info('Event source field do not match',['status' => 403,'request'=>$request]);
            return MyHelper::response(false,'Event source field do not match', [],403);
        }

        if ($event_source == 'contact') {
            $contact = (new Contact)->find($event_source_id);
            if (!$contact) {
                Log::channel('event_history')->info('Event source do not exist',['status' => 403,'request'=>$request]);
                return MyHelper::response(false,'Event source do not exist', [],403);
            }
        }elseif ($event_source == 'agent') {
            $agent = (new User)->find($event_source_id);
            if (!$agent) {
                Log::channel('event_history')->info('Event source do not exist',['status' => 403,'request'=>$request]);
                return MyHelper::response(false,'Event source do not exist', [],403);
            }
        }else{
            $customer = (new Customer)->find($event_source_id);
            if (!$customer) {
                Log::channel('event_history')->info('Event source do not exist',['status' => 403,'request'=>$request]);
                return MyHelper::response(false,'Event source do not exist', [],403);
            }
        }

        if(isset($request['ticket_id'])){
            $ticket = Ticket::where('id',$request['ticket_id'])->first();
            if($ticket){
                $ticket_id=$request['ticket_id'];
            }else{
                Log::channel('event_history')->info('ticket not found',['status' => 404,'request'=>$request]);
                return MyHelper::response(false,'ticket not found',[],404);
            }
        }
        if(isset($request['contact_id'])){
            $contact = Contact::where('id',$request['contact_id'])->first();
            if($contact){
                $contact_id=$request['contact_id'];
            }else{
                Log::channel('event_history')->info('contact not found',['status' => 404,'request'=>$request]);
                return MyHelper::response(false,'contact not found',[],404);
            }
        }


        if(isset($request['order_id'])){
            $order = Order::where('id',$request['order_id'])->first();
            if($order){
                $order_id=$request['order_id'];
            }else{
                Log::channel('event_history')->info('order not found',['status' => 404,'request'=>$request]);
                return MyHelper::response(false,'order not found',[],404);
            }
        }

        



        $event = new Event;
        $event->event_title = $title;
        $event->event_type = $event_type;
        $event->note = $note;
        $event->event_location = $event_location;
        $event->remind_time = $request->remind_time;
        $event->remind_type = $remind_type;
        $event->event_assign_team = $team_id;
        $event->event_assign_agent = $agent_id;
        $event->event_source_id = $event_source_id;
        $event->event_source = $event_source;
        $event->ticket_id = $ticket_id ?? null;
        $event->contact_id =  $contact_id ?? null;
        $event->order_id =  $order_id ?? null;
       
        $event->groupid = auth()->user()->groupid;
        $event->created_by = auth()->user()->id;
        
        if ($event->save()) {
            Log::channel('event_history')->info('Create event successfully',['status' => 200,'request'=>$request]);
            return MyHelper::response(true,'Create event successfully', ['id' => $event->id],200);
        }else{
            Log::channel('event_history')->info('Create event failed',['status' => 403,'request'=>$request]);
            return MyHelper::response(false,'Create event failed', [],403);
        }
    }

    
    /**
    * @OA\Get(
    *     path="/api/v3/event/{eventId}",
    *     tags={"Event"},
    *     summary="Find event by eventId",
    *     description="<h2>This API will find event by {eventId} and return only a single record</h2>",
    *     operationId="show",
    *     @OA\Parameter(
    *         name="eventId",
    *         in="path",
    *         description="<h4>This is the id of the event you are looking for</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         example=1,
    *         required=true,
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="string", example="1"),
    *             @OA\Property(property="event_title",type="string", example="this is example event"),
    *             @OA\Property(property="event_source",type="string", example="contact"),
    *             @OA\Property(property="event_source_id",type="string", example="1"),
    *             @OA\Property(property="event_status",type="string", example="publish"),
    *             @OA\Property(property="remind_time",type="string", example="2022-02-11 15:24:00"),
    *             @OA\Property(property="remind_type",type="string", example="91"),
    *             @OA\Property(property="days_noti_before_type",type="string", example="1"),
    *             @OA\Property(property="event_type",type="string", example="1"),
    *             
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Event do not exist",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="boolean", example="Event do not exist"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         )
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     }
    * )
    */
    public function show($id)
    {
        $event = Event::where('id', $id)->first();
        if (!$event) {       
            Log::channel('event_history')->info('Create event successfully',['status' => 200,'id'=>$id]);     
            return MyHelper::response(false,'Event not found',[],404);
        }else{
            Log::channel('event_history')->info('Successfully',['status' => 200,'id'=>$id]);
            return MyHelper::response(true,'Successfully',$event,200);
        }
    }

    /**
    * @OA\Put(
    *     path="/api/v3/event/{$eventId}",
    *     tags={"Event"},
    *     summary="Update event by eventId",
    *     description="<h2>This API will update a event by eventId and the value json form below</h2><br><code>Press try it out button to modified</code>",
    *     operationId="update",
    *     @OA\Parameter(
    *         name="eventId",
    *         in="path",
    *         example=1,
    *         description="<h4>This is the id of the event you need update</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         required=true,
    *     ),
    *     @OA\RequestBody(
    *       required=true,
    *       description="<table id='my-custom-table'>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <td><b id='require'>Required</b></td>
                </tr>
                <tr>
                    <th>event_tile</th>
                    <td>Title of ticket</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>event_type</th>
                    <td>Category of event</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>Note</th>
                    <td>Note of event</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>event_location</th>
                    <td>event venue</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>remind_time</th>
                    <td>time to remind</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>remind_type</th>
                    <td>type remind of event</td>
                    <td>false ('date','daily','weekly','monthly','yearly')</td>
                </tr>
                <tr>
                    <th>handling_team</th>
                    <td>handling parts</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>handling_agent</th>
                    <td>agent handling this event</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>event_source</th>
                    <td>source event</td>
                    <td>false (contact,agent,customer)</td>
                </tr>
                <tr>
                    <th>event_source_id</th>
                    <td>id of source event</td>
                    <td>false</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       @OA\JsonContent(
    *         @OA\Property(property="event_title", type="string", example="Sinh nhật"),
    *         @OA\Property(property="event_type", type="string", example="1"),
    *         @OA\Property(property="note", type="string", example="this is example"),
    *         @OA\Property(property="remind_time", type="string", example="15/03/2022 17:20:00"),
    *         @OA\Property(property="remind_type", type="string", example="daily"),
    *         @OA\Property(property="handling_team", type="number", example="1"),
    *         @OA\Property(property="handling_agent", type="number", example="1"),
    *         @OA\Property(property="event_source", type="string", example="1"),
    *         @OA\Property(property="event_source_id", type="number", example="1"),
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Update successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Update event successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Update failed",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="remind_type field do not match"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Will be return event not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Event do not exist"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function update(Request $request, $id)
    {
        $event = Event::where('id', $id)->first();
        $request['updated_by']=auth()->user()->id;;
        if (!$event) {
            Log::channel('event_history')->info('Event do not exist',['status' => 404,'request'=>$request]);
            return MyHelper::response(false,'Event do not exist', [],404);
        }
        $request = array_filter($request->all()); 

        if (array_key_exists('remind_time', $request)) {
	        if (strlen($request['remind_time']) == 10) {
	            $request['remind_time'] = $request['remind_time'] . '00:00:00';
	        }
        	// $remind_time = Carbon::createFromFormat('d/m/Y H:i:s', $request['remind_time'])->format('Y-m-d H:i:s');
            $remind_time = Carbon::parse($request['remind_time'])->format('Y-m-d H:i:s');
        	$request['remind_time'] = $remind_time;
	    }

        if (array_key_exists('remind_type', $request)) {
        	if (!in_array($request['remind_type'], $this->array_remind_type)) {
                Log::channel('event_history')->info('remind_type field do not match',['status' => 404,'request'=>$request]);
	            return MyHelper::response(false,'remind_type field do not match', [],403);
        	}
        }

        if (array_key_exists('handling_team', $request)) {
        	$check_team = (new Team)->where('team_id',$request['handling_team'])->first();
        	if ($check_team) {
        		$request['event_assign_team'] = $request['handling_team'];
        	}else{
                Log::channel('event_history')->info('handling_team field do not match',['status' => 404,'request'=>$request]);
	            return MyHelper::response(false,'handling_team field do not match', [],403);
        	}
            
        	if (array_key_exists('handling_agent', $request)) {
	        	$check_agent = (new TeamStaff)->where('team_id',$check_team->team_id)->get()->pluck('agent_id')->toArray();
	        	if (in_array(json_decode( $request['handling_agent']),$check_agent)) {
	        		$request['event_assign_agent'] = $request['handling_agent'];
	        	}else{
                    Log::channel('event_history')->info('handling_agent field do not match',['status' => 404,'request'=>$request]);
		            return MyHelper::response(false,'handling_agent field do not match', [],403);
	        	}
        	}else{
                Log::channel('event_history')->info('handling_agent field is require',['status' => 404,'request'=>$request]);
	            return MyHelper::response(false,'handling_agent field is require', [],403);
        	}
        }

       	if (array_key_exists('event_source', $request) && array_key_exists('event_source_id', $request)) {
       		if (!in_array($request['event_source'], $this->array_event_source)) {
                Log::channel('event_history')->info('Event source field do not match',['status' => 404,'request'=>$request]);
            	return MyHelper::response(false,'Event source field do not match', [],403);
       		}else{
       			if ($request['event_source'] == 'contact') {
		            $contact = (new Contact)->find($request['event_source_id']);
		            if (!$contact) {
                        Log::channel('event_history')->info('Event source do not exist',['status' => 404,'request'=>$request]);
		                return MyHelper::response(false,'Event source do not exist', [],403);
		            }
		        }elseif ($request['event_source'] == 'agent') {
		            $agent = (new User)->find($request['event_source_id']);
		            if (!$agent) {
                        Log::channel('event_history')->info('Event source do not exist',['status' => 404,'request'=>$request]);
		                return MyHelper::response(false,'Event source do not exist', [],403);
		            }
		        }else{
		            $customer = (new Customer)->find($request['event_source_id']);
		            if (!$customer) {
                        Log::channel('event_history')->info('Event source do not exist',['status' => 404,'request'=>$request]);
		                return MyHelper::response(false,'Event source do not exist', [],403);
		            }
		        }
       		}
       	}

       	if (array_key_exists('event_type', $request)) {
       		$check_event_type = (new EventType)->find($request['event_type']);
	        if (!$check_event_type) {
                Log::channel('event_history')->info('Event type field do not exist',['status' => 404,'request'=>$request]);
	            return MyHelper::response(false,'Event type field do not exist', [],403);
	        }	
       	}

           DB::beginTransaction();
           try {
            DB::commit();
           if(isset($request['ticket_id'])){
            $ticket = Ticket::where('id',$request['ticket_id'])->first();
            if($ticket){
                $event->ticket_id=$request['ticket_id'];
                $event->save();
            }else{
                Log::channel('event_history')->info('ticket not found',['status' => 404,'request'=>$request]);
                return MyHelper::response(false,'ticket not found',[],404);
            }
        }
        if(isset($request['contact_id'])){
            $contact = Contact::where('id',$request['contact_id'])->first();
            if(!$contact){
                Log::channel('event_history')->info('contact not found',['status' => 404,'request'=>$request]);
                return MyHelper::response(false,'contact not found',[],404);
            }else{
                $event->contact_id=$request['contact_id'];
                $event->save();
            }
        }
        if(isset($request['order_id'])){
            $order = Order::where('id',$request['order_id'])->first();
            if(!$order){
                Log::channel('event_history')->info('order not found',['status' => 404,'request'=>$request]);
                return MyHelper::response(false,'order not found',[],404);
            }else{
                $event->order_id=$request['order_id'];
                $event->save();
            }
        }
        
        if ($event->update($request)) {
            Log::channel('event_history')->info('Update event successfully',['status' => 200,'request'=>$request]);
        	return MyHelper::response(true,'Update event successfully', [],200);
        }else{
            Log::channel('event_history')->info('Update event failed',['status' => 403,'request'=>$request]);
            return MyHelper::response(false,'Update event failed', [],403);
        }
    } catch (\Exception $ex) {
        DB::rollback();
        return MyHelper::response(false,$ex->getMessage(), [],403);
    }

    }

   

    /**
    * @OA\Delete(
    *     path="/api/v3/event/{eventId}",
    *     tags={"Event"},
    *     summary="Delete a event by eventId",
    *     description="<h2>This API will delete a event by eventId</h2>",
    *     operationId="destroy",
    *     @OA\Parameter(
    *         name="eventId",
    *         in="path",
    *         example=1,
    *         description="<h4>This is the id of the event you need delete</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         required=true,
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Delete event successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Delete event successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Event not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Event not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function destroy($id)
    {
        $event = Event::where('id', $id)->first();
        if (!$event) {        
            Log::channel('event_history')->info('Event not found',['status' => 404,'id'=>$id]);    
            return MyHelper::response(false,'Event not found',[],404);
        }else{
            $eventact = Event::find($id);
            $eventact->delete();
            Log::channel('event_history')->info('Delete Event Successfully',['status' => 200,'id'=>$id]); 
            return MyHelper::response(true,'Delete Event Successfully', [],200);
        }
    }


        /**
    * @OA\POST(
    *     path="/api/v3/event/ticket/{eventId}",
    *     tags={"Event"},
    *     summary="Create a new ticket id for event ",
    *     description="<h2>This API will create a ticket_id in a event by ticketId and the value json form below</h2><br>",
    *     operationId="EventTicket",
    *     @OA\Parameter(
    *       name="eventId",
    *       in="path",
    *       description="<table id='my-custom-table'>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <td><b id='require'>Required</b></td>
                </tr>
                <tr>
                    <th>private</th>
                    <td>(0: normal, 1: internal note)</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>content</th>
                    <td>Content of comment</td>
                    <td>true</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       required=true,
    *     ),
    *     @OA\RequestBody(
    *       required=true,
    *       @OA\JsonContent(
    *         required={"private","content"},
    *         @OA\Property(property="ticket_id", type="string", example="4491"),
    *     
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="add a ticket_id successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="add a ticket_id successfully successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="If $evenId do not exist or invalid will be return ticket not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="contact not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=405,
    *         description="If $ticketId already exist on a event will be return this event already have ticket",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="this event already have ticket"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */


    public function EventTicket(Request $request,$id)
    {
        $event = Event::where('id', $id)->first();
        if (!$event) {           
            Log::channel('event_history')->info('Event not found',['status' => 404,'id'=>$id,'request'=>$request]); 
            return MyHelper::response(false,'Event not found',[],404);
        }else{      
            if($event->ticket_id){
                Log::channel('event_history')->info('this event already have ticket',['status' => 403,'id'=>$id,'request'=>$request]);
                return MyHelper::response(true,'this event already have ticket',['event_id'=>$event->id,'ticket_id'=>$event->ticket_id],403); 
            }else{
                if(isset($request['ticket_id'])){
                    $ticket = Ticket::where('id',$request['ticket_id'])->first();
                    if($ticket){
                        $event->ticket_id=$request['ticket_id'];
                        $event->save();
                        Log::channel('event_history')->info('this event already have ticket',['status' => 403,'id'=>$id,'request'=>$request]);
                        return MyHelper::response(true,'add ticket successfully',['event_id'=>$event->id,'ticket_id'=>$event->ticket_id],200); 
                    }else{
                        Log::channel('event_history')->info('ticket not found',['status' => 404,'id'=>$id,'request'=>$request]);
                        return MyHelper::response(false,'ticket not found',[],404);
                    }
                }else{
                    Log::channel('event_history')->info('please enter ticket_id',['status' => 500,'id'=>$id,'request'=>$request]);
                    return MyHelper::response(false,'please enter ticket_id',[],500);
                }
            }  
        }
    }
}
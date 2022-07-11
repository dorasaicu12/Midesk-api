<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\v3\TicketRequest;
use Illuminate\Support\Str;
use App\Http\Functions\CheckTrigger;
use App\Http\Functions\MyHelper;
use App\Models\CustomField;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\Event;
use App\Models\Contact;
use App\Models\GroupTable;
use App\Models\ModelsTrait;
use App\Models\Team;
use App\Models\TeamStaff;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use App\MarcoModel;
use Auth;
use DB;
/**
 * @group  Tickets Management
 *
 * APIs for managing tickets
 */
class TicketController extends Controller
{
    use ModelsTrait;

    /**
    * @OA\Get(
    *     path="/api/v3/ticket",
    *     tags={"Ticket"},
    *     summary="Get list ticket",
    *     description="Get list ticket with param",
    *     operationId="index",
    *     @OA\Parameter(
    *         name="page",
    *         in="query",
    *         description="Num of page",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Parameter(
    *         name="limit",
    *         in="query",
    *         description="Total number of records to get",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Parameter(
    *         name="search",
    *         in="query",
    *         description="Condition to find ticket ({$key}={$value})",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Parameter(
    *         name="order_by",
    *         in="query",
    *         description="Sort follow condition ({column}={DESC or ASC})",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Parameter(
    *         name="fields",
    *         in="query",
    *         description="Column to get {$column1},{$column2},{$column3}",
    *         required=false,
    *         explode=true,
    *     ),
    *     @OA\Response(
    *         response=201,
    *         description="Successful",
    *         @OA\JsonContent(
    *             @OA\Property(property="status", type="boolean", example="true"),
    *             @OA\Property(property="message", type="string", example="Successfully"),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="data",type="object",
    *                   @OA\Property(property="id",type="object", example="1"),
    *                   @OA\Property(property="ticket_id",type="object", example="1"),
    *                   @OA\Property(property="title",type="object", example="this is example ticket"),
    *                   @OA\Property(property="assign_agent",type="object", example="1"),
    *                   @OA\Property(property="requester",type="object", example="3"),
    *                   @OA\Property(property="get_tickets_detail",type="array", 
    *                     @OA\Items(type="object",
    *                       @OA\Property(property="id",type="string", example="1"),
    *                       @OA\Property(property="title",type="string", example="this is title example"),
    *                       @OA\Property(property="content",type="string", example="this is content example"),
    *                     ),
    *                   ),
    *                 ),
    *                 @OA\Property(property="current_page",type="string", example="1"),
    *                 @OA\Property(property="first_page_url",type="string", example="null"),
    *                 @OA\Property(property="next_page_url",type="string", example="null"),
    *                 @OA\Property(property="last_page_url",type="string", example="null"),
    *                 @OA\Property(property="prev_page_url",type="string", example="null"),
    *                 @OA\Property(property="from",type="string", example="1"),
    *                 @OA\Property(property="to",type="string", example="1"),
    *                 @OA\Property(property="total",type="string", example="1"),
    *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/ticket"),
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
        $tickets = new Ticket;
        $tickets = $tickets->getDefault($req);
        return MyHelper::response(true,'Successfully',$tickets,200);
    }
    
    /**
    * @OA\Get(
    *     path="/api/v3/ticket/{ticketId}",
    *     tags={"Ticket"},
    *     summary="Find the ticket by ID",
    *     description="Will be return a single ticket",
    *     operationId="show",
    *     @OA\Parameter(
    *         name="ticketId",
    *         in="path",
    *         description="ID of ticket",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )    
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful",
    *         @OA\JsonContent(
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="object", example="1"),
    *             @OA\Property(property="ticket_id",type="object", example="1"),
    *             @OA\Property(property="title",type="object", example="this is example ticket"),
    *             @OA\Property(property="assign_agent",type="object", example="1"),
    *             @OA\Property(property="requester",type="object", example="3"),
    *             @OA\Property(property="get_tickets_detail",type="array", 
    *               @OA\Items(type="object",
    *                 @OA\Property(property="id",type="string", example="1"),
    *                   @OA\Property(property="title",type="string", example="this is title example"),
    *                   @OA\Property(property="content",type="string", example="this is content example"),
    *                 ),
    *               ),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Invalid Ticket ID",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="boolean", example="Ticket not found"),
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
        $ticket = Ticket::showOne($id);
        if($ticket){
            return MyHelper::response(true,'Successfully',$ticket,200);
        }else{
            return MyHelper::response(false,'Ticket not found',$ticket,404);
        }
    }
    /**
    * @OA\POST(
    *     path="/api/v3/ticket",
    *     tags={"Ticket"},
    *     summary="Create the ticket with json form",
    *     description="Can create many ticket in a request with array ticket []",
    *     operationId="store",
    *     @OA\RequestBody(
    *       required=true,
    *       description="typing form data to create",
    *       @OA\JsonContent(
    *         required={"title","content"},
    *         @OA\Property(property="title", type="string", example="Phiếu khiếu nại 2"),
    *         @OA\Property(property="content", type="string", example="Nội dung phiếu số 1"),
    *         @OA\Property(property="channel", type="string", example="Facebook"),
    *         @OA\Property(property="priority", type="string", example="1"),
    *         @OA\Property(property="category", type="string", example="1"),
    *         @OA\Property(property="contact", type="object", required={"name","email"},
    *           @OA\Property(property="name",type="string", example="Nguyễn văn A"),
    *           @OA\Property(property="facebook_id",type="string", example=""),
    *           @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
    *           @OA\Property(property="phone",type="string", example="0123456789"),
    *           @OA\Property(property="zalo_id",type="string", example=""),
    *         )
    *       ),
    *     ),
    *     @OA\Response(
    *         response=405,
    *         description="Invalid input"
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function store(TicketRequest $request)
    {   
        if (array_key_exists(0, $request->all())) {
            // multiple insert
            foreach ($request->all() as $key => $value) {
                $this->create_or_update_ticket($value);
            }
            return MyHelper::response(true,'Created Ticket Successfully', [],200);
        }else{
            return $this->create_or_update_ticket($request->all());   
        }
    }
    
   
    /**
    * @OA\Put(
    *     path="/api/v3/ticket/{ticketId}",
    *     tags={"Ticket"},
    *     summary="Update the ticket by ID",
    *     description="Update a ticket with input",
    *     operationId="update",
    *     @OA\Parameter(
    *         name="ticketId",
    *         in="path",
    *         description="ID of ticket",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         ),
    *     ),
    *     @OA\RequestBody(
    *       required=true,
    *       description="typing form data to update",
    *       @OA\JsonContent(
    *         required={"title","content"},
    *         @OA\Property(property="title", type="string", example="Phiếu khiếu nại 2"),
    *         @OA\Property(property="content", type="string", example="Nội dung phiếu số 1"),
    *         @OA\Property(property="channel", type="string", example="Facebook"),
    *         @OA\Property(property="priority", type="string", example="1"),
    *         @OA\Property(property="category", type="string", example="1"),
    *         @OA\Property(property="contact", type="object", required={"name","email"},
    *           @OA\Property(property="name",type="string", example="Nguyễn văn A"),
    *           @OA\Property(property="facebook_id",type="string", example=""),
    *           @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
    *           @OA\Property(property="phone",type="string", example="0123456789"),
    *           @OA\Property(property="zalo_id",type="string", example=""),
    *         ),
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Update successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Update ticket successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="If $ticketId do not exist or invalid will be return ticket not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Ticket not found"),
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

        $ticket = Ticket::showOne($id);
        if($ticket){
            return $this->create_or_update_ticket($request->all(),$id);
        }else{
            return MyHelper::response(true,'404 not found',$ticket,404);
        }
        
    }
    /**
    * @OA\Delete(
    *     path="/api/v3/{ticketId}",
    *     tags={"Ticket"},
    *     summary="Deletes a ticket",
    *     operationId="destroy",
    *     @OA\Parameter(
    *         name="ticketId",
    *         in="path",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Delete ticket successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Delete ticket successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Ticket not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Ticket not found"),
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
        $ticket = Ticket::showOne($id);
        if (!$ticket) {
            return MyHelper::response(false,'Ticket Not Found', [],404);
        }else{
            $ticket->update(['is_delete' => Ticket::DELETED,'is_delete_date' => date('Y-m-d H:i:s'),'is_delete_creby' => auth::user()->id]);
        }
        return MyHelper::response(true,'Delete Ticket Successfully', [],200);
    }
    /**
     * Add a comment for ticket
     *
     * @urlParam  id id of ticket
     * @bodyParam content required content of a comment
     * @bodyParam private required status of a comment (default = 0 (public)) Example: 1
    
     * @response 200 {
     *   "status": true,
     *   "message": "Successfully",
     *   "data": []
     * }

     * @response 404 {
     *   "status": false,
     *   "message": "Resource Not Found",
     *   "data": []
     * }
     */
    public function comment(Request $request, $id)
    {

        $ticket = Ticket::showOne($id);
        if($ticket){
            if (!$id) {
                return MyHelper::response(false,'ticket to find for creat comment not found', [],404);
            }
            $comment = $this->create_comment($id,$request->all(),'');
            return MyHelper::response(true,'Created Comment Successfully', [],200);

        }else{
            return MyHelper::response(true,'404 not found,please enter a correct ticket"s id',$ticket,401);
        }

    }

    /**
     * Upload File for ticket
     *
     * @urlParam  id required id of comment
     * @bodyParam file[] file required file upload for comment.

     * @response 200 {
     *   "status": true,
     *   "message": "Successfully",
     *   "data": []
     * }

     * @response 404 {
     *   "status": false,
     *   "message": "Resource Not Found",
     *   "data": []
     * }
     */
    public function attachfile(Request $request, $id)
    {
        if (!$id) {
            return MyHelper::response(false,'Upload Failed', [],500);
        }
        if($request->hasFile('file')){
            $groupTable = GroupTable::select('upload_size')->find(auth::user()->groupid);
            $uploadsize_limit = $groupTable->upload_size;
            $count_total_size = 0;
            $count_total_file = 20;
            if (count($request->file('file')) > $count_total_file) {
                return MyHelper::response(false,'Exceed the number of files uploaded !', [],201);
            }
            if (count($request->file('file')) > 1) {
                foreach ($request->file('file') as $file) {
                    if ($count_total_size > $uploadsize_limit) {
                        return MyHelper::response(false,'Uploaded file exceeds the allowed size !', [],201);
                    }
                    $count_total_size += $file->getSize() / 1024 / 1024;
                }
            }else{
                if ($request->file('file')[0]->getSize() / 1024 / 1024 > $uploadsize_limit) {
                    return MyHelper::response(false,'Uploaded file exceeds the allowed size !', [],201);
                }
            }
            // Call api from midesk core
            $check_storage = $this->CallApiCheckStorage($request->file('file'));
            if (!$check_storage) {
                    return MyHelper::response(false,'Uploaded file exceeds the allowed size !', [],201);
            }
            $comment = $this->create_comment($id,$request->all(),'');
        }else{
            return MyHelper::response(false,'File upload not found!', [],400);
        }
        if ($comment) {
            return MyHelper::response(true,'Upload File Successfully', [],200);
        }else{
            return MyHelper::response(false,'Upload File Failed', [],201);
        }
    }


    /* function call api check storage from core */
    public function CallApiCheckStorage($req='')
    {
        return true;
    }
    
    public function create_contact($data)
    {
        //Kiểm tra tồn tại contact hay không
        $contact = new Contact;
        $phone = $data['phone'];
        $email = $data['email'];
        if(!empty($email) && !empty($phone)){
            $check_contact = $contact->select(['id','address','email','phone','fullname'])
                ->where(function($q) use ($phone, $email) {
                    $q->where('phone', $phone)->orWhere('email', $email);
                })->first();
        }elseif(empty($phone)){
            $check_contact = $contact->select(['id','address','email','phone','fullname'])
                ->where('email',$email)
                ->first();
        }else{
            $check_contact = $contact->select(['id','address','email','phone','fullname'])
                ->where('phone',$phone)
                ->first();
        }
        //Thêm mới Contact
        if(!$check_contact){
            $contact->groupid     = $data['groupid'];
            $contact->fullname    = $data['name'];
            $contact->phone       = $data['phone'];
            $contact->email       = $data['email'];
            $contact->facebook_id = $data['facebook_id'] ?? '';
            $contact->zalo_id     = $data['zalo_id'];
            $contact->channel     = $data['channel'];
            $contact->datecreate  = $data['time'];
            $contact->creby       = $data['creby'];

            $contact->save();

            if(!$contact){
                return MyHelper::response(false,'Create Contact Failed', [],500);
            }
            $requester = $contact->id;
        }else{
            $requester = $check_contact->id;
        }
        return $requester;
    }

    /**
     * Form Assign Ticket
    
     * @response 200 {
     *   "status": true,
     *   "message": "Create ticket successfully",
     *   "data": {
     *       "teams": "{$object_teams}",
     *       "priority": "[$array_priority]",
     *       "category": "[$array_category]",
     *       "listEmail": "[$array_email]"
     *   }
     * }
     */
    public function assignForm()
    {
        $groupid = auth::user()->groupid;
        $team = TeamStaff::with('Agent')->select('team_id','agent_id')->where('groupid',$groupid)->get();
        $teams = [];
        foreach ($team as $element) {
            $teamid = $element['team_id'];
            unset($element['agent_id']);
            unset($element['team_id']);
            $teams[$teamid][] = $element->Agent;
        }
        $data['teams'] = $teams;
        $data['priority'] = TicketPriority::all()->toArray();
        $data['category'] = TicketCategory::with('Child.Child')->where([['groupid',$groupid],['parent','0']])->get()->toArray();
        $data['listEmail'] = User::select('email')->where('groupid',$groupid)->get()->pluck('email')->toArray();
        return MyHelper::response(true,'Successfully', $data,200);
    }


    public function marcoList()
    {
        $groupid = auth::user()->groupid;
        $team = MarcoModel::get();
        $data['text']='';
        foreach($team as $key=> $element){
            $data['text']=$element['action'];
        }
        $data = $team;

        return MyHelper::response(true,'Successfully', $data,200);
    }

}

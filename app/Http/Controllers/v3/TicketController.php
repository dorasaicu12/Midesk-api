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

    /**
     * @SWG\Get(
     *     path="/api/v3/ticket",
     *     tags={"Ticket"},
     *     summary="Ticket",
     *     operationId="ticket",
     *     security={{"bearer":{}}},
     *     description="Return a list ticket",
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         type="string",
     *         description="number of page",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         type="string",
     *         description="limit record to get",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="search",
     *         in="query",
     *         type="string",
     *         description="search data by ( {key}={value} )",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="order_by",
     *         in="query",
     *         type="string",
     *         description="Sort order by ( {id}:{asc} )",
     *         required=false,
     *     ),
     *     @SWG\Parameter(
     *         name="fields",
     *         in="query",
     *         type="string",
     *         description="Get custom record result ( title,priority,status,content )",
     *         required=false,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @SWG\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
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
     * Show A Ticket.
     *
     * @queryParam ticket int required id of the ticket.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Successfully",
     *   "data": {
            "id":2,
            "title": "phiếu test từ dev 2",
            "ticket_id": "0113",
            "channel": "api",
            "content": "Lạc Long Quân - Tân Bình 2",
            "status": "close"
         }
     * }

     * @response 404 {
     *   "status": false,
     *   "message": "Resource Not Found",
     *   "data": {}
     *  }
     */
    public function show($id)
    {
        $ticket = Ticket::showOne($id);
        return MyHelper::response(true,'Successfully',$ticket,200);
    }

    /**
     * Create A Ticket
     *
     * @bodyParam title string required The title of the ticket.
     * @bodyParam content string required The content of the ticket.
     * @bodyParam priority int required ticket priority. Example: 1
     * @bodyParam category int required category of the ticket. Example: 1
     * @bodyParam contact.*.name string required string name of contact.
     * @bodyParam contact.*.email email required email of contact. Example: admin@gmail.com
     * @bodyParam contact.*.phone string required phone of contact. Example: 01234567890
     * @bodyParam private int optional default public.

     
     * @response 200 {
     *   "status": true,
     *   "message": "Create ticket successfully",
     *   "data": {
     *       "id": "{$id}",
     *       "ticket_id": "{$ticket}"
     *   }
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Resource not found"
     * }

     * @response  400 {
     *   "status": false,
     *   "message": "Title is require",
     *   "data": {}
     * }
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
     * Update Ticket
     * @urlParam  ticket required The ID of the ticket. Example: 1
     * @bodyParam title string required The title of the ticket.
     * @bodyParam content string required The content of the ticket.
     * @bodyParam priority int required ticket priority. Example: 1
     * @bodyParam category int required category of the ticket. Example: 1
     * @bodyParam status string status of the ticket. Example: closed
     * @bodyParam contact.*.name string required string name of contact.
     * @bodyParam contact.*.email email required email of contact. Example: admin@gmail.com
     * @bodyParam contact.*.phone string required phone of contact. Example: 01234567890
     
     * @response 200 {
     *   "status": true,
     *   "message": "Update ticket successfully",
     *   "data": []
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Resource Not Found"
     * }
     
     * @response  400 {
     *   "status": false,
     *   "message": "Title is require",
     *   "data": {}
     * }
     */
    public function update(Request $request, $id)
    {
        return $this->create_or_update_ticket($request->all(),$id);
    }
    /**
     * Destroy Ticket
     
     * @response 200 {
     *   "status": true,
     *   "message": "Delete ticket successfully",
     *   "data": []
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Resource Not Found"
     * }

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
        if (!$id) {
            return MyHelper::response(false,'Create Failed', [],500);
        }
        $comment = $this->create_comment($id,$request->all(),'');
        return MyHelper::response(true,'Created Comment Successfully', [],200);
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

}

<?php

namespace App\Http\Controllers\Group_2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Group_2\TicketRequest;
use Illuminate\Support\Str;
use App\Http\Functions\CheckTrigger;
use App\Http\Functions\MyHelper;
use App\Models\CustomField;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\Event;
use App\Models\Contact;
use App\Models\GroupTable;
use App\Models\TicketsTrait;
use Auth;
use DB;
/**
 * @group  Tickets Management
 *
 * APIs for managing tickets
 */
class TicketController extends Controller
{
    use TicketsTrait;
    /**
     * Get All Ticket
     *
     * @queryParam  page optional get record in page Example: page=1
     * @queryParam  limit optional limit record Example: limit=5
     * @queryParam  key_search optional key search ($key = status:and optional status is key search, and will condition, if you want search many field add (,) before) Example: key_search={$key}
     * @queryParam  q optional value to look for what you need Example: q=text to looking for something
     * @queryParam  order_by optional sort record ( $key = id:desc,records_id,asc optional sort by id order by desc, if you want search many field add (,) before) Example: order_by={$key}
     * @queryParam  fields optional get column you want (default get all) Example: fields=id,fullname,status,category,...
    
     * @response 200 {
     *   "status": true,
     *   "message": "Successfully",
     *   "data": {
            "current_page": 1,
            "data": [{
                    "id":1,
                    "title": "phiếu test từ dev 1",
                    "ticket_id": "2112",
                    "channel": "api",
                    "content": "Lạc Long Quân - Tân Bình 1",
                    "status": "pending"
                },{
                    "id":2,
                    "title": "phiếu test từ dev 2",
                    "ticket_id": "2113",
                    "channel": "api",
                    "content": "Lạc Long Quân - Tân Bình 2",
                    "status": "close"
            }],
            "first_page_url": "{$URL}/ticket?limit=5&key_search=status%3Aand%2Ctitle%3Aor&q=new&order_by=id%3Aasc%2Cticket_id%3Adesc&fields=title%2Cpriority%2Cstatus%2Ccategory%2Cassign_agent%2Cassign_team%2Crequester%2Cgroupid&page=1",
            "from": 1,
            "last_page": 17,
            "last_page_url": "{$URL}/ticket?limit=5&key_search=status%3Aand%2Ctitle%3Aor&q=new&order_by=id%3Aasc%2Cticket_id%3Adesc&fields=title%2Cpriority%2Cstatus%2Ccategory%2Cassign_agent%2Cassign_team%2Crequester%2Cgroupid&page=17",
            "next_page_url": "{$URL}/ticket?limit=5&key_search=status%3Aand%2Ctitle%3Aor&q=new&order_by=id%3Aasc%2Cticket_id%3Adesc&fields=title%2Cpriority%2Cstatus%2Ccategory%2Cassign_agent%2Cassign_team%2Crequester%2Cgroupid&page=2",
            "path": "{$URL}/ticket",
            "per_page": "5",
            "prev_page_url": null,
            "to": 5,
            "total": 83
        }
     *}

     * @response 404 {
     *   "status": false,
     *   "message": "Resource Not Found",
     *   "data": []
     * }
     */
    public function index(Request $request)
    {
        $req = $request->all();
        $tickets = new Ticket;
        if (!empty($req['k']) && !in_array($req['k'], $tickets->getFillable())) {
            return MyHelper::response(false,'Key Search Not Found',[],404);
        }
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
        $ticket = Ticket::with(['getTicketsDetail.getTicketCreator' => function ($q)
        {
            $q->select(['id','fullname','picture',DB::raw("'https://dev2021.midesk.vn/upload/images/userthumb/' as path"),]);
        }])->find($id);
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
            $contact->facebook_id = $data['facebook_id'];
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



    //==================== SLA ============================//

    function testTaoTicketSLA(){
        //thông tin ins lấy từ các param truyền vào khi tạo ticket, tương tự trigger
        //sao khi tạo ticket thành công thì bắt đầu check tạo SLA

        //SLA VER 2
        $groupid = 2;
        $time = time();
        $array  = array(
            'groupid'     => $groupid,
            'ticket_id'   => $id, //đây là id ticket, id khóa chính tự tăng
            'status'      => $ins['status'],
            'channel'     => $ins['channel'],
            'category'    => $ins['category'],
            'id_customer' => $ins['requester'],
            'priority'    => $ins['priority'],
            'datecreate'  => $ins['datecreate'],
        );
        switch ($ins['status']) {
            case 'new':
                $array['date_open'] = $time;             
                break;
            case 'open':
                $array['date_open'] = $time;             
                break;
            case 'pending':
                $array['date_open'] = $time;                   
                $array['date_pending'] = $time;       
                break;
            case 'solved':
                $array['date_open'] = $time;                
                $array['date_solved'] = $time;                              
                break;
            case 'closed':
                $array['date_open'] = $time;                  
                $array['date_closed'] = $time;
                break;
            default:
                break;
        }
        $sql = "SELECT s.conditions,s.operator,s.actions,s.time_bonus, w.detail, w.holiday , w.full_time, w.id
                FROM ticket_sla s 
                LEFT JOIN time_work w ON s.timework_id = w.id 
                WHERE s.groupid = ".$groupid." AND s.public = 1 
                ORDER BY s.id DESC";
        $sla = $this->ticket->customQuery($sql,'arr');
        $this->processingSLA_new($array,$sla);
    }
    function testUpdateTicket(){
        //sao khi có update ticket, thì lấy các thông tin đã đc uipdate bỏ vào SLA tính xem có thây đổi chủ đề, độ ưu tiên k để check lại SLA
        //sao khi tạo ticket thành công thì bắt đầu check tạo SLA
        
        //SLA VER 2
        $groupid = 2;
        $time = time();
        $array  = array(
            'groupid'     => $groupid,
            'ticket_id'   => $key_ticket_id,
            'status'      => $upd['status'],
            'channel'     => $check['channel'],
            'category'    => $upd['category'],
            'id_customer' => $check['requester'],
            'priority'    => $upd['priority'],
            'datecreate'  => $check['datecreate'],
        );
        switch ($status) {
            case 'new':
                $array['date_open'] = $time;             
                break;
            case 'open':
                $array['date_open'] = $time;             
                break;
            case 'pending':
                $array['date_open'] = $time;                  
                $array['date_pending'] = $time;            
                break;
            case 'solved':
                $array['date_open'] = $time;                
                $array['date_solved'] = $time;                              
                break;
            case 'closed':
                $array['date_open'] = $time;                 
                $array['date_closed'] = $time;
                break;
            default:
                break;
        }
        $sql = "SELECT s.conditions,s.operator,s.actions,s.time_bonus, w.detail, w.holiday , w.full_time, w.id
                FROM ticket_sla s 
                LEFT JOIN time_work w ON s.timework_id = w.id 
                WHERE s.groupid = ".$groupid." AND s.public = 1 
                ORDER BY s.id DESC";
        $sla = $this->ticket->customQuery($sql,'arr');
        $this->processingSLA_new($array,$sla);
    }

}

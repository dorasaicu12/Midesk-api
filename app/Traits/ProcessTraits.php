<?php
namespace App\Traits;

use Auth;
use DB;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Functions\MyHelper;
use App\Http\Functions\CheckTrigger;
use App\Http\Requests\v3\ContactRequest;
use App\Models\TicketDetail;
use Illuminate\Support\Facades\Log;
use App\Models\Contact;
use App\Models\Tags;
use App\Models\TicketLabel;
use App\Models\User;
use App\Models\TeamStaff;
use App\Models\Team;
use App\Models\Event;
use Illuminate\Support\Facades\Crypt;
trait ProcessTraits {

    public $groupid = '';

    function __construct()
    {
        if (auth::user()) {
            $this->groupid = auth::user()->groupid;
        }
    }
    
    public function create_or_update_ticket($req,$id_t = '')
    {     
        $groupid = $this->groupid;
        $creby   = auth::user()->id;
        $contact = [];
        $action  = 'create';
        $message = 'vừa tạo phiếu';
        $channel_list = [
            'vinid',
            'vb_driver_app',
            'unit',
            'chat',
            'facebook',
            'zalo',
            'email',
            'voice',
            'sms',
            'webform',
            'api',
            'event',
            'web',
        ];
        $field = [];
        $time     = time();
        $title    = $req['title'];
        $content  = $req['content'] ?:'ko có dữ liệu';
        $priority = $req['priority'] ?? 4;
        $category = $req['category'] ?? null; 
        $label    = $req['label'] ?? null;
        $status=$req['status'] ?? null;
        $status   = array_key_exists('status', $req) ? $req['status'] : 'new';  
        $private  = array_key_exists('private',$req) ? 1 : 0;    
        $channel  = 'api';
        
        //kiem tra tag co ton tai hay ko
        if(isset($req['tags'])){
            $tag=    $req['tags'];
            
            $tags= explode(',', $tag);
            
            foreach(explode(',', $tag) as $val){
              $tagcheck= Tags::where('id',$val)->first();
               if(!$tagcheck){
                $message=array();
                   $message[]=['message'=>'tag '.$val.' can not be found'];
               }
   
            }
            
            if(is_array($message)){
               foreach($message as $v ){
                   foreach($v as $v2){
                       return MyHelper::response(false,$v2, [],404); 
                   }
                }
            }
        }

      
        if (isset($req['label'])) {
          $checklable=  TicketLabel::where('id',$req['label'])->first();
          if(!$checklable){
            return MyHelper::response(false,'label can not be found', [],404); 
          }
         $label_creaby= $checklable['createby'];
         $req['label_creby']=$checklable['createby'];
        }
        if (isset($req['event_id']) && rtrim($req['event_id'])!=='') {
            $checkEvent=  Event::where('id',$req['event_id'])->first();
            if(!$checkEvent){
              return MyHelper::response(false,'event can not be found', [],404); 
            }
          }

        //check handle agent va handle team 
        if(array_key_exists('assign_agent', $req)){
            $user_id = $req['assign_agent'];
            $check_team = (new User)->where('id',$user_id)->first();
            if (!$check_team) {
                return MyHelper::response(false,'assign_agent field do not match', [],403);
            }
        }
        if (array_key_exists('assign_team', $req)) {
            $team_id = $req['assign_team'];
            $check_team = (new Team)->where('team_id',$team_id)->first();
            if (!$check_team) {
                return MyHelper::response(false,'assign_team field do not match', [],403);
            }
        }

        if (array_key_exists('assign_agent', $req) && array_key_exists('assign_team', $req)) {
            $team_id = $req['assign_team'];
            $check_team = (new Team)->where('team_id',$team_id)->first();
            if (!$check_team) {
                return MyHelper::response(false,'assign_team field do not match', [],403);
            }
            $agent_id = $req['assign_agent'];
            $check_agent = (new TeamStaff)->where('team_id',$check_team->team_id)->get()->pluck('agent_id')->toArray();
            if (!in_array($agent_id,$check_agent)) {
                return MyHelper::response(false,'assign_agent field do not match', [],403);
            }
        }


       
        if (array_key_exists('channel', $req)) {
            $cn = str_replace(' ', '_', strtolower($req['channel']));
            if (in_array($cn, $channel_list)) {
                $channel = $cn;
            }
        }

        DB::beginTransaction();  
        try{        
        DB::commit();
            // tạo contact
            if (array_key_exists('contact_id', $req)) {
                $contact_id = intval($req['contact_id']);
                $check_contact2 = Contact::where('id',$contact_id)->get();
                $check_contact=  count($check_contact2);
                if ($check_contact > 0) {
                    $requester = $contact_id;
                }else{
                    $requester = null;
                }
            }else{
                $contact['name']        = $req['contact']['name'];
                $contact['email']       = $req['contact']['email'];
                $contact['phone']       = $req['contact']['phone'];
                $contact['facebook_id'] = $req['contact']['facebook_id'] ?? '';
                $contact['zalo_id']     = $req['contact']['zalo_id'] ?? '';
                $contact['channel']     = $channel;
                $contact['groupid']     = $groupid;
                $contact['creby']       = $creby;
                $contact['time']        = $time;
                $requester = $this->create_contact($contact);
            }

            if (!$requester) {
                return MyHelper::response(false,'Contact not found!', [],404); 
            }
            
            if ($id_t) {
                // Cập nhật phiếu
                $ticket2 = Ticket::where('id',$id_t)->first();
                // echo $ticket2['id'];
                // exit;
                $ticket = Ticket::where('id',$id_t)->first();
                
                if (!$ticket) {
                    return MyHelper::response(false,'Ticket not found!', [],404); 
                }
                $action = 'update';
                $message = 'vừa cập nhật phiếu';
                $assign_team    = array_key_exists('assign_team', $req) ? $req['assign_team'] : $ticket->assign_team;
                $assign_agent    = array_key_exists('assign_agent', $req) ? $req['assign_agent'] : $ticket->assign_agent;
                $ticket->dateupdate     = $time;
                $ticket->requester      = $requester ?? $ticket->requester;
                if(isset($req['tags'])){
                    $req['tag']=$req['tags'];
                }
                
                $request = array_filter($req);
                // phần lọc riêng giá trị giữa create và update
                // if (array_key_exists('cotact_id', $req)) {
                //     $contact_id = intval($req['cotact_id']);
                //     $check_contact = Contact::where('id',$contact_id)->first();
                //     if($check_contact){
                //         $requestcontact = array_filter($req['contact']);
                //         $check_contact->update($requestcontact);
                //     }else{
                //         return MyHelper::response(false,'Contact Not found', [],400);
                //     }
                // }        
                $ticketval=new Ticket;
                $ticketval=$ticketval->where('id',$id_t)->first();
                $ticket->title=isset($req['title']) ? $req['title'] : $ticketval->title;
                $ticket->channel=isset($req['channel']) ?$req['channel'] :$ticketval->channel;
                $ticket->priority= isset($req['priority']) ? $req['priority'] :$ticketval->priority;
                $ticket->category=isset($req['category']) ? $req['category']  :$ticketval->category;
                $ticket->status=isset($req['status']) ? $req['status'] :$ticketval->status;
                $ticket->tag=isset($req['tags']) ? $req['tags']  :$ticketval->tags;
                $ticket->label=isset($req['label']) ? $req['label']  :$ticketval->label;
                $ticket->assign_team=isset($req['assign_team']) ?$req['assign_team']  :$ticketval->assign_team;
                $ticket->assign_agent=isset($req['assign_agent']) ?$req['assign_agent']  : $ticketval->assign_agent;
                $ticket->event_id=isset($req['event_id']) ?$req['event_id'] :$ticketval->event_id;
                if(!isset($ticketval->first_reply_time)){
                    $ticket->first_reply_time=time();
                }
                
                $ticket->save();
                
                
            }else{   
                //Tạo phiếu
                $input = [
                    'groupid'   => $groupid,
                    'channel'   => $channel,
                    'timework'  => $time,
                    'requester' => $requester,
                ];
                //Check Trigger
                
                $output = CheckTrigger::check_trigger_ticket_roundrobin($input);
                $priority     = array_key_exists('priority', $output) ? $output['priority'] : $priority;
                $category     = array_key_exists('category',$output) ? $output['category'] : $category;
                $assign_agent = array_key_exists('assign_agent',$output) ? $output['assign_agent'] : 0;
                $assign_team  = array_key_exists('assign_team', $output) ? $output['assign_team'] : 0;
                $status       = array_key_exists('status',$output) ? $output['status'] : 'new';
                $label        = array_key_exists('label',$output) ? $output['label'] : null;
                
                $ticket = new Ticket;
                $ticket->requester      = $requester;
                $ticket->requester_type = 'contact';
                $ticket->assign_agent   =$req['assign_agent'] ?? $assign_agent;
                $ticket->assign_team    =$req['assign_team'] ?? $assign_team;
                $ticket->createby       = $creby;
                
                $ticket->groupid        = $groupid;
                if ($groupid == '103') {
                    $ticket->mt_orderid     = array_key_exists('mt_orderid',$req) ? $req['mt_orderid'] : null;
                    $ticket->mt_productid   = array_key_exists('mt_productid',$req) ? $req['mt_productid'] : null;
                    $ticket->mt_qty         = array_key_exists('mt_qty',$req) ? $req['mt_qty'] : null;
                    $tdetail['mt_orderid'] = $ticket->mt_orderid;
                    $tdetail['mt_productid'] = $ticket->mt_productid;
                }
                // phần lọc riêng giá trị giữa create và update
                $ticket->title          = $title;
                $ticket->datecreate     = $time;
                $ticket->status         =$req['status'] ?? $status;
                $ticket->channel        = $channel;
                $ticket->priority       = $priority;
                $ticket->event_id=$req['event_id'] ?? null;
                $ticket->category       = $category;
                $ticket->label          = $req['label'] ?? null;
                $ticket->label_creby          =$label_creaby ?? null;
                $ticket->tag          = $req['tags'] ?? null;
            }
            //chức năng chung giữa update và create
            if (!empty($req['custom_field'])) {
                foreach ($req['custom_field'] as $key => $value) {
                    $key = str_replace('dynamic_', '', $key);
                    $check_field = CustomField::where([['id',$key],['groupid',$groupid]])->first();
                    if (!$check_field) {
                        return MyHelper::response(true,'Custom Field '.$key.' Do not exists', null,400);
                    }else{
                        $field[$key] = $value;
                    }
                }
                $custom_field = json_encode($field);
                $ticket->custom_fields  = $custom_field;
            }
            $ticket->save(); 


            // sla
            $sladata  = array(
                'groupid'     => $groupid,
                'ticket_id'   => $ticket->id, //đây là id ticket, id khóa chính tự tăng
                'status'      => $status,
                'channel'     => $channel,
                'category'    => $category,
                'id_customer' => $requester,
                'priority'    => $priority,
                'datecreate'  => $time,
            );
            switch ($sladata['status']) {
                case 'new':
                    $sladata['date_open'] = $time;             
                    break;
                case 'open':
                    $sladata['date_open'] = $time;             
                    break;
                case 'pending':
                    $sladata['date_open'] = $time;                   
                    $sladata['date_pending'] = $time;       
                    break;
                case 'solved':
                    $sladata['date_open'] = $time;                
                    $sladata['date_solved'] = $time;                              
                    break;
                case 'closed':
                    $sladata['date_open'] = $time;                  
                    $sladata['date_closed'] = $time;
                    break;
                default:
                    break;
            }
            $sql = "SELECT s.conditions,s.operator,s.actions,s.time_bonus, w.detail, w.holiday , w.full_time, w.id
                    FROM ticket_sla s 
                    LEFT JOIN time_work w ON s.timework_id = w.id 
                    WHERE s.groupid = ".$groupid." AND s.public = 1 
                    ORDER BY s.id DESC";
            $sla = Ticket::customQuery($sql,true);
            $this->processingSLA_new($sladata,$sla);
            // tạo chi tiết phiếu
            
            sleep(1);
            $tdetail['content'] = $content;
            $tdetail['private'] = $private;
            if($content!=''){
                $ticket_detail = $this->create_comment($ticket->id,$tdetail,$action);
                
            $ndata = array(
                'assign_agent' => $assign_agent,
                'assign_team'  => $assign_team,
                'ticket_detail'=> $ticket_detail,
                'message'      => $message,
                'content'      => $content,
                'channel'      => $channel,
                'status'      => $status
            );
            $this->create_notifications($ndata);
            }
            
            if ($action == 'create') {
                return MyHelper::response(true,'Created Ticket Successfully', ['id' => $ticket_detail->id,'ticket_id' => "#".$ticket_detail->ticket_id],200);
            }else{
                return MyHelper::response(true,'Updated Ticket Successfully', [],200);
            }
        } catch (\Exception $ex){
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage().'at line'.$ex ->getLine(), [],500);

        }

    }

    public function processingSLA_new($input = array(), $sla = array()){
        $time = time();

        $groupid                = @$input['groupid']?:0;
        $input_status           = @$input['status']?:'new';
        $input_ticket_id        = @$input['ticket_id']?:'0';
        $input_date_open        = @$input['date_open']?:0;
        $input_date_pending     = @$input['date_pending']?:0;
        $input_date_solved      = @$input['date_solved']?:0;
        $input_date_closed      = @$input['date_closed ']?:0;
        $input_pending_duration = @$input['pending_duration']?:0;
        $input_datecreate       = @$input['datecreate']?:$time;
        $input_channel  = @$input['channel']?:'web';
        $input_category = @$input['category']?:'';
        $input_customer = @$input['id_customer']?:0;
        $input_priority = @$input['priority']?:'-1';


        $time = time();
        foreach ($sla as $key => $value) {
            $conditions = json_decode(@$value['conditions'],true);
            if (!is_array($conditions) && !($conditions instanceof Traversable)) $conditions = array();
            $actions    = json_decode(@$value['actions'],true);
            if (!is_array($actions) && !($actions instanceof Traversable)) $actions = array();
            $action_check = false;
            $timework = $value['detail'];
            $holiday  = $value['holiday'];
            //CHECK CONDITONS
            if($value['operator'] == 'all'){ //AND
                //vì điều kiện là AND, nên bắt buộc phải thỏa tất cả điều kiện => $count_tmp = count($count_con);
                
                $count_tmp = 0;
                foreach ($conditions as $cond) {
                    switch ($cond['condition']) {
                        case 'channel':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_channel,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_channel,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;
                        // case 'category':
                        //     if($cond['operator'] == 'is'){
                        //         if(count(array_intersect($input_category, $cond['value'])) > 0){
                        //             $count_tmp++;
                        //         }
                        //     }elseif($cond['operator'] == 'not_is'){
                        //         if(count(array_intersect($input_category, $cond['value'])) == 0){
                        //             $count_tmp++;
                        //         }
                        //     }
                        //     break;
                        case 'category':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_category,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_category,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;        
                        case 'customer':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_customer,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_customer,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;
                        default:
                            break;
                    }   
                }
                $count_con = count($conditions);
                $count_tmp = 0;
                if($count_con == $count_tmp){
                    $action_check = true;
                }
            }elseif($value['operator'] == 'any'){ //OR
                //vì điều kiện là OR, nên bắt buộc phải thỏa tất cả điều kiện => $count_tmp > 0;
                $count_tmp = 0;
                foreach ($conditions as $cond) {
                    switch ($cond['condition']) {
                        case 'channel':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_channel,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_channel,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;
                        case 'category':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_category,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                 if(!in_array($input_category,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;     
                        // case 'category':
                        //     if($cond['operator'] == 'is'){
                        //         if(count(array_intersect($input_category, $cond['value'])) > 0){
                        //             $count_tmp++;
                        //         }
                        //     }elseif($cond['operator'] == 'not_is'){
                        //         if(count(array_intersect($input_category, $cond['value'])) == 0){
                        //             $count_tmp++;
                        //         }
                        //     }
                        //     break;    
                        case 'customer':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_customer,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_customer,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;
                        default:
                            break;
                    }

                    if($count_tmp > 0){
                        $action_check = true;
                        break;
                    }   
                }
            }

            //ACTIONS
            $insert = array();
            if($action_check){
                foreach ($actions as $act) {  
                    $ids = array_map(function($item){ return $item['pri'];}, $act['value']);
                    $list_sla = array_combine($ids,$act['value']); 
                    switch ($act['name']) {  
                        case 'first_reply_time': //trả lời đầu tiên                           
                            if(key_exists($input_priority,$list_sla)){
                                $insert['sla_first_reply'] = $list_sla[$input_priority]['val']*60;
                            }
                            break; 
                        case 'periodic_update': //thời gian cập nhật định kì                            
                            if(key_exists($input_priority,$list_sla)){
                                $insert['sla_updated'] = $list_sla[$input_priority]['val']*60;
                            }
                            break;
                        case 'solved_time': //thời gian cập nhật định kì                            
                            if(key_exists($input_priority,$list_sla)){
                                $insert['sla_solved'] = $list_sla[$input_priority]['val']*60;
                            }
                            break;
                        default:
                            break;
                    }
                    
                }

                if(!empty($input_ticket_id) && !empty($insert)){
                    try {
                        $check = Ticket::show_by_id('ticket_sla_remain',array('ticket_id' => $input_ticket_id,'groupid' => $groupid));
                        if(empty($check)){
                            //new bỏ qua cho CGV
                            // if($groupid == 95 && $input_status == 'new'){
                            //     return false;
                            // }
                            $insert['ticket_id']            = $input_ticket_id;
                            $insert['status']               = $input_status;
                            $insert['pending_duration']     = $input_pending_duration;
                            $insert['date_first_reply']     = 0;
                            $insert['date_open']            = $input_date_open;
                            $insert['date_pending']         = $input_date_pending;
                            $insert['date_solved']          = $input_date_solved;
                            $insert['date_closed']          = $input_date_closed;
                            $insert['date_periodic_update'] = $time;
                            $insert['datecreate']           = $input_datecreate;
                            $insert['groupid']              = $groupid;

                            $insert['remain_first_reply'] = MyHelper::sla(($insert['datecreate'] + $insert['pending_duration']), array('work_time' => $timework,'holiday' => $holiday), @$insert['sla_first_reply']?:0);

                            $insert['remain_periodic_update'] = MyHelper::sla(($insert['date_periodic_update'] + $insert['pending_duration']), array('work_time' => $timework,'holiday' => $holiday), @$insert['sla_updated']?:0); 

                            $insert['remain_solved'] = MyHelper::sla(($insert['datecreate'] + $insert['pending_duration']), array('work_time' => $timework,'holiday' => $holiday), @$insert['sla_solved']?:0); 

                            Ticket::insert('ticket_sla_remain',$insert);
                        }else{
                            $insert['date_periodic_update'] = $time;
                            switch ($input_status) {
                                case 'new':
                                    if(empty($check['date_open'])) $insert['date_open'] = $time; 
                                    break;
                                case 'open':
                                    if(empty($check['date_open'])) $insert['date_open'] = $time;
                                    if($check['date_pending'] > 0){
                                        $insert['pending_duration'] = $check['pending_duration'] + ($time - $check['date_pending']);
                                        $insert['date_pending'] = 0;
                                    }
                                    break;
                                case 'pending':
                                    if(empty($check['date_pending'])) $insert['date_pending'] = $time;
                                    if(empty($check['date_open'])) $insert['date_open'] = $time;
                                    break;
                                case 'solved':
                                    if(empty($check['date_solved'])) $insert['date_solved'] = $time;
                                    if(empty($check['date_open'])) $insert['date_open'] = $time;
                                    break; 
                                case 'closed':
                                    if(empty($check['date_closed'])) $insert['date_closed'] = $time;
                                    if(empty($check['date_solved'])) $insert['date_solved'] = $time;
                                    if(empty($check['date_open'])) $insert['date_open'] = $time;
                                    break;            
                                default:
                                    break;
                            }
                            if(empty($check['date_first_reply'])){
                                $insert['date_first_reply'] = $time;
                            }

                            $insert['remain_first_reply'] = MyHelper::sla(($check['datecreate'] + $check['pending_duration']), array('work_time' => $timework,'holiday' => $holiday), @$insert['sla_first_reply']?:0);

                            $insert['remain_periodic_update'] = MyHelper::sla(($insert['date_periodic_update'] + $check['pending_duration']), array('work_time' => $timework,'holiday' => $holiday), @$insert['sla_updated']?:0); 

                            $insert['remain_solved'] = MyHelper::sla(($check['datecreate'] + $check['pending_duration']), array('work_time' => $timework,'holiday' => $holiday), @$insert['sla_solved']?:0); 


                            $resp = Ticket::show_by_id('ticket_sla_remain',array('ticket_id' => $input_ticket_id)); 
                        }                        
                    } catch (Exception $e) {
                        
                    }
                }

                $sla_output['time_work'] = $value['id'];
                $sla_output['status'] = 'enable';


                return $sla_output;
            }           
        }
    }
    public function create_comment($ticket_id,$data,$action = '')
    {
        $ticket = Ticket::where('id',$ticket_id)->first();
        if (!$ticket) {
            return false;
        }
        $html_msg = '';
        $ticket_detail = new TicketDetail;
        $check_tkd = $ticket_detail->where('ticket_id',$ticket_id)->orderBy('id','desc')->first();
        //Tạo chi tiết phiếu
        if (array_key_exists('private', $data)) {
            if (intval($data['private']) == 1) {
                $data['private'] = 1;
                $ms = 'private';
            }else if(intval($data['private']) == 2){
                $data['private'] = 2;
                $ms = 'private';
            } else{
                $data['private'] = 0; 
                $ms = 'public';
            }
        }else{
            $data['private'] = $check_tkd->private;
        }
        /// upload file ///
        if (array_key_exists('file', $data)) {
            if (count($data['file']) > 1) {
                $ar = [];
                foreach ($data['file'] as $file) {
                    $fname = md5($file->getClientOriginalName(). time()).'.'.$file->getClientOriginalExtension();  
                    array_push($ar, 
                        [
                        'file_original' => $file->getClientOriginalName(),
                        'file_extension' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'file_name' => $fname
                        ]
                    );
                    $file->move(public_path().'/files/', $fname);
                }
                $ticket_detail->file_multiple = json_encode($ar);
            }else{
                $fname = md5($data['file'][0]->getClientOriginalName(). time()).'.'.$data['file'][0]->getClientOriginalExtension(); 
                $ticket_detail->file_original = $data['file'][0]->getClientOriginalName();
                $ticket_detail->file_extension = $data['file'][0]->getClientOriginalExtension();
                $ticket_detail->file_size = $data['file'][0]->getSize();
                $ticket_detail->file_name = $fname;
                $data['file'][0]->move(public_path().'/files/', $fname); 
            }
            $type='file';
        }else{
            $type='text';
        }
        ///// end upload file //////
        $ticket_detail->title          = $data['title'] ?? $ticket->title;
        $ticket_detail->ticket_id      = $ticket->id;
        $ticket_detail->groupid        = $ticket->groupid;
        $ticket_detail->channel        = $ticket->channel;
        $ticket_detail->private        = $data['private'];
        $ticket_detail->type           = $type;
        $ticket_detail->createby       = auth::user()->id;
        $ticket_detail->status         = $ticket->status;
        $ticket_detail->createby_level = auth::user()->level;
        $ticket_detail->datecreate     = time();
        if ($check_tkd) {
            if (rtrim($ticket_detail->title) != rtrim($check_tkd->title)) {
                $html_msg .= '<div><i>Đã cập nhật tiêu đề phiếu <span class="private-note"><del>'.$check_tkd->title.'</del></span> thành <span class="private-note">'.$ticket_detail->title.'</span></i></div>';
            }
            if (rtrim($ticket_detail->status) != rtrim($check_tkd->status)) {
                $html_msg .= '<div><i>Đã cập nhật trạng thái phiếu <span class="private-note"><del>'.$check_tkd->status.'</del></span> thành <span class="private-note">'.$ticket_detail->status.'</span></i></div>';
            }
            if (rtrim($ticket_detail->private) != rtrim($check_tkd->private)) {
                $html_msg .= '<div><i>Đã cập nhật chế độ xem của phiếu <span class="private-note"><del>'.$ms.'</del></span> thành <span class="private-note">'.($ticket_detail->private == 1 ? 'private' : 'public').'</span></i></div>';
            }
            if (rtrim($ticket_detail->file_name) != rtrim($check_tkd->file_name) && $ticket_detail->file_name != '') {
                $html_msg .= '<div class="attachfile-content">
                                <label>File đính kèm</label>
                                <img src="https://dev2021.midesk.vn/public/images/rar.gif">
                                <a href="'.$ticket_detail->file_name.'" target="_blank">'.$ticket_detail->file_name.'</a>
                            </div>';
            }
            if (array_key_exists('priority', $data)) {
                $html_msg .= '<div><i>Đã cập nhật độ ưu tiên phiếu từ <span class="private-note"><del>'.$ticket_detail->priority.'</del></span> thành <span class="private-note">'.$data['priority'].'</span></i></div>';
            }
            if (array_key_exists('mt_status', $data)) {
                $html_msg .= '<div><i>Đã cập nhật Trạng thái sản phẩm <span class="private-note"><del>'.$ticket->mt_status.'</del></span> thành <span class="private-note">'.$data['mt_status'].'</span></i></div>';
            }
            if (rtrim($ticket_detail->file_multiple) != rtrim($check_tkd->file_multiple) && $ticket_detail->file_multiple != '') {
                $files = json_decode($ticket_detail->file_multiple);
                $html_msg .= '<div class="attachfile-content"><label>File đính kèm</label>';
                foreach ($files as $key => $file) {
                    $html_msg .= '
                        <div>
                            <img src="https://dev2021.midesk.vn/public/images/rar.gif">
                            <a href="'.$file->file_name.'" target="_blank">'.$file->file_name.'</a>
                        </div>';
                }
                $html_msg .= '</div>';
            }
            if (array_key_exists('content', $data)) {
                $html_msg .= $data['content'];
            }
        }else{
            if ((array_key_exists('mt_orderid', $data) && $data['mt_orderid'] != '' ) && (array_key_exists('mt_productid', $data)  && $data['mt_productid'] != '' )) {
                $html_msg .= '<div><i>Đã tạo đơn hàng '.$data['mt_orderid'].' thành công</i></div>';
            }
            if (array_key_exists('content', $data)) {
                $html_msg .= $data['content'];
            }
        }
        $ticket_detail->content = $html_msg;
        $ticket_detail->save();

        $ndata = array(
            'assign_agent' => $ticket->assign_agent,
            'assign_team'  => $ticket->assign_team,
            'ticket_detail'=> $ticket_detail,
            'message'      => 'Vừa tạo nội dung mới',
            'channel'      => $ticket->channel,
            'status'       => $ticket->status
        );

        $this->create_notifications($ndata);
        return $ticket;
    }

    public function create_notifications($data)
    {

        $ins_notifi = array(
                'groupid'   => $this->groupid,
                'id_user'   => $data['assign_agent'],
                'id_team'   => $data['assign_team'],
                'type'      => 'ticket',
                'title'     => '<b>Hệ thống</b> '.$data['message'].' <b>#'.$data['ticket_detail']->ticket_id.' - '.$data['ticket_detail']->title.'</b>',
                'ticket_id' => $data['ticket_detail']->id,
                'channel'   => $data['channel'],
                'custom'    => json_encode(array('id' => $data['ticket_detail']->id,'name' => $data['ticket_detail']->title , 'status' => $data['status'])),
                'view'      => '',
                'del_agent' => '',
            );
        if (array_key_exists('content', $data)) {
            $ins_notifi['content'] = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', mb_substr(strip_tags($data['content']), 0, 100));
        }
        Ticket::insert('notifications',$ins_notifi);
    }

    public function OrderUpdateMinhTuanMobile(Request $request,$ordid)
    {
        $data = [];
        if (!$request->status) {
            return MyHelper::response(false,'Status is required',[],404);
        }
        
        $order = new Ticket;
        $check_order = $order->checkExist_mtmb($ordid,$request->product_id);

        if (!$check_order) {
            Log::channel('orders_history')->info('Order not found',['status' => 404, 'id'=>$ordid,'request'=>$request->all()]);
            return MyHelper::response(false,'Resource Not Found',[],404);
        }else{
            $check_order->mt_status = $request->status;
            $data['mt_status'] = $request->status;
            $this->create_comment($check_order->id,$data,'');
            $check_order->save();
            return MyHelper::response(true,'Status order update successfully',[],200);
        }
    }

    public function OrderStoreMinhTuanMobile(Request $request)
    {
        $groupid = $this->groupid;
        if (!$request->order_id) {
            Log::channel('orders_history')->info('Order id not found',['status' => 200, 'request'=>$request->all()]);
            return MyHelper::response(false,'Order id not found', [],200);
        }
        $id_contact = Contact::checkContact($request->customer_phone);
        if (!$id_contact) {
            $contact = new Contact;
            $contact->groupid       = $groupid;
            $contact->fullname      = $request->customer_name;
            $contact->phone         = $request->customer_phone;
            $contact->email         = $request->customer_email;
            $contact->address       = $request->customer_address.'/'.$request->customer_locate;
            $contact->save();
            $id_contact = $contact->id;
        }else{
            $id_contact = $id_contact->id;
        }
        $product_list = $request->products;
        
        $state = true;
        $message = ['Created Order successfully'];
        foreach ($product_list as $key => $prd) {
            if (!$prd['code_product'] || !$prd['name_product']) {
                $message[] = 'Product '.($prd['code_product'] ? $prd['code_product'] : $prd['name_product']).' không tồn tại code, không thể tạo sản phẩm này';
                break;
            }
            // check product code
            $id_product = Product::checkProductByCode($prd['code_product'],$groupid);
            if (!$id_product) {
                $product = new Product;
                $product->groupid      = $groupid;
                $product->channel      = 'web';
                $product->product_code = $prd['code_product'];
                $product->product_name = $prd['name_product'];
                $product->product_full_name = $prd['name_product'];
                $product->product_orig_price = $prd['cost_product'];
                $product->product_price = $prd['price_product'];
                $product->product_description = $prd['notes_product'];
                $product->created_by = 'api';
                $product->save();
                $id_product = $product->id;
            }else{
                $id_product = $id_product->id;
            }
            // check ticket exist
            $ticket = new Ticket;
            $check_ticket = $ticket->checkExist_mtmb($request->order_id,$prd['code_product']);
            $ticket_id = '';
            if ($check_ticket) {
                $ticket_id = $check_ticket->id;
            }
            if (array_key_exists('image_product', $prd) && $prd['image_product'] != '') {
                $image = $prd['image_product'];
            }else{
                $image = 'https://cskh.midesk.vn/public/images/no_image.png';
            }

            // create ticket
            $params = [];
            $params['contact_id'] = $id_contact;
            $params['title'] = "Tạo đơn hàng #".$request->order_id." với mã sản phẩm ".$prd['code_product'];
            $params['content'] = "
                                <p>Kính gửi khách hàng : <b>".$request->customer_name."</b></p>
                                <p>Điện thoại : <b>".$request->customer_phone."</b></p>
                                <p>Email : <b>".$request->customer_email."</b></p>
                                <p>Địa chỉ : <b>".$request->customer_address.'/'.$request->customer_locate."</b></p>
                                <p>Ghi chú : <b>".$request->customer_note."</b></p>
                                <h5>Thông tin đơn hàng <b>#".$request->order_id."</b></h5>
                                <table class='table table-bordered table-striped table-hover' style='font-size:12px;'>
                                    <thead>
                                        <tr>
                                            <th class='text-center' >Ảnh</th>
                                            <th class='text-center' >Mã</th>
                                            <th class='text-center' >Sản phẩm</th>
                                            <th class='text-center' >Giá</th>
                                            <th class='text-center' >Giá Nhập</th>
                                            <th class='text-center' >Màu</th>
                                            <th class='text-center' >Tùy chọn</th>
                                            <th class='text-center' >Quà</th>
                                            <th class='text-center' >Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class='text-center'><img style='width:50px;' src=".$image." ></td>
                                            <td class='text-center' style='vertical-align: middle;'><span>".$prd['code_product']."</span></td>
                                            <td class='text-center' style='vertical-align: middle;'><span>".$prd['name_product']."</span></td>
                                            <td class='text-center' style='vertical-align: middle;'><span>".$prd['price_product']."</span></td>
                                            <td class='text-center' style='vertical-align: middle;'><span>".$prd['cost_product']."</span></td>
                                            <td class='text-center' style='vertical-align: middle;'><span>".$prd['color_product']."</span></td>
                                            <td class='text-center' style='vertical-align: middle;'><span>".$prd['option_product']."</span></td>
                                            <td class='text-center' style='vertical-align: middle;'><span>".$prd['gift_product']."</span></td>
                                            <td class='text-center' style='vertical-align: middle;'><span>".$prd['notes_product']."</span></td>
                                        </tr>
                                    </tbody>
                                </table>";
            $params['channel'] = "api";
            $params['mt_orderid'] = $request->order_id;
            $params['mt_productid'] = $id_product;
            $params['mt_qty'] = ($key + 1).'/'.count($product_list);

            if (!$this->create_or_update_ticket($params,$ticket_id)) {
                $state = false;
            }
            
        }
        if ($state) {
            return MyHelper::response(true,$message,[],200);
        }else{
            Log::channel('orders_history')->info('Created Order Failed',['status' => 403,['message'=>json_encode($message)] ,'request'=>$request->all()]);
            return MyHelper::response(false,'Created Order Failed', [],403);
        }
    }

    public function ContactStoreTrungSon(ContactRequest $req)
    {
        $groupid = $this->groupid;
        $creby   = auth::user()->id;
        $fullname = $req->fullname;
        $phone    = $req->phone ?: "";
        $email    = $req->email ?: "";
        $address  = $req->address ?: "";    
        $gender   = $req->gender ?: "";    
        $ext_contact_id = $req->ext_contact_id ?: "";
        $channel_list  = ['facebook', 'zalo', 'webform', 'email', 'web', 'api'];
        $channel = 'api';
        if (array_key_exists('channel', $req)) {
            if (in_array($req->channel, $channel_list)) {
                $channel = $req->channel;
            }
        }

        $time     = time();
        $field = [];
        DB::beginTransaction();
        try {
            //Kiểm tra tồn tại contact hay không
            
            $check_contact = Contact::checkContact($phone,$email,$ext_contact_id);

            DB::commit();

            //Thêm mới Contact

            if(!$check_contact){
                $contact->ext_contact_id = $ext_contact_id;
                if (rtrim($req->phone_other) != rtrim($phone)) {
                    $contact->phone_other = $req->phone_other;
                }
                $contact->unit_name = $req->unit_name;
                $contact->branch = $req->branch;
                $contact->card_type = $req->card_type;
                $contact->province = $req->province;
                $contact->creator = $req->creator;
                $contact->created = $req->created;
                $contact->identity_number  = $req->identity_number;
                $contact->identity_date  = $req->identity_date;
                $contact->identity_location  = $req->identity_location;
                $contact->firstname = $fullname;
                $contact->address       = $address;
                $contact->groupid       = $groupid;
                $contact->fullname      = $fullname;
                $contact->phone         = $phone;
                $contact->email         = $email;                  
                $contact->gender        = $gender;                  
                $contact->channel       = $channel;
                $contact->datecreate    = $time;
                $contact->creby         = $creby;
                if (!empty($req->custom_field)) {
                    foreach ($req->custom_field as $key => $value) {
                        $key = str_replace('dynamic_', '', $key);
                        $check_field = CustomField::where('id',$key)->first();
                        if (!$check_field) {
                            return MyHelper::response(true,'Custom Field '.$key.' Do not exists', null,200);
                        }else{
                            $field[$key] = $value;
                        }
                    }
                    $custom_field = json_encode($field);    
                    $contact->custom_fields = $custom_field;
                }
                $contact->save();
                if(!$contact){
                    return MyHelper::response(false,'Create Contact Failed', [],500);
                }
                $id = $contact->id;


                usleep(1000);

                $new_contact = Contact::select('ext_contact_id')->find($id);                
                Log::channel('contact_history')->info('Create contact successfully',['id'=>$id,'request'=>$req->all()]);
                return MyHelper::response(true,'Create contact successfully', ['id' => $id,'contact_id' => $new_contact->ext_contact_id],200);
            }else{
                if (rtrim($req->phone_other) != rtrim($phone)) {
                    $check_contact->phone_other = $req->phone_other;
                }
                $check_contact->unit_name = $req->unit_name;
                $check_contact->branch = $req->branch;
                $check_contact->card_type = $req->card_type;
                $check_contact->province = $req->province;
                $check_contact->creator = $req->creator;
                $check_contact->created = $req->created;
                $check_contact->identity_number  = $req->identity_number;
                $check_contact->identity_date  = $req->identity_date;
                $check_contact->identity_location  = $req->identity_location;
                $check_contact->firstname = $fullname;
                $check_contact->address     = $address;
                $check_contact->groupid     = $groupid;
                $check_contact->fullname    = $fullname;
                $check_contact->phone       = $phone;
                $check_contact->email       = $email;                  
                $check_contact->gender      = $gender;                  
                $check_contact->channel     = $channel;
                $check_contact->datecreate  = $time;
                $check_contact->creby       = $creby;

                if($check_contact->save()){ 
                    Log::channel('contact_history')->info('Updated contact successfully',['req'=>$req->all()]);
                    return MyHelper::response(false,'Updated Contact successfully', [],200);
                }
                return MyHelper::response(true,'Contact already exists', ['id' => $check_contact->id,'contact_id' => $check_contact->ext_contact_id],200);
            }  

        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],500);
        }
    }

    public function ContactUpdateTrungSon(ContactRequest $req,$id)
    {
        $groupid = $this->groupid;
        $creby   = auth::user()->id;

        $fullname = $req->fullname;
        $phone    = $req->phone ?: "";
        $email    = $req->email ?: "";
        $address  = $req->address ?: "";    
        $gender   = $req->gender ?: "";   
        $channel_list  = ['facebook', 'zalo', 'webform', 'email', 'web', 'api'];
        $channel = 'api';
        if (array_key_exists('channel', $req)) {
            if (in_array($req->channel, $channel_list)) {
                $channel = $req->channel;
            }
        }
        $time     = time();
        $field = [];
        DB::beginTransaction();
        try {
            //Kiểm tra tồn tại contact hay không
            
            $check_contact = Contact::checkContact($phone,$email,$id);

            DB::commit();

            //Cập nhật Contact
            if(!$check_contact){
                return MyHelper::response(true,'Contact Not found', [],400);
            }else{
                if (array_key_exists('ext_contact_id', $req) && $req['ext_contact_id'] != '') {                    
                    $check_contact->ext_contact_id = $req->ext_contact_id;
                }
                if (!array_key_exists('phone_other', $req) || $req['phone_other'] == '') {
                    $check_contact->phone_other = $req->phone_other;
                }
                $check_contact->unit_name = $req->unit_name;
                $check_contact->branch = $req->branch;
                $check_contact->card_type = $req->card_type;
                $check_contact->province = $req->province;
                $check_contact->creator = $req->creator;
                $check_contact->created = $req->created;
                $check_contact->identity_number  = $req->identity_number;
                $check_contact->identity_date  = $req->identity_date;
                $check_contact->identity_location  = $req->identity_location;
                $check_contact->firstname = $fullname;
                $check_contact->fullname    = $fullname;
                $check_contact->phone       = $phone;
                $check_contact->email       = $email;
                $check_contact->address     = $address;                  
                $check_contact->gender      = $gender;                  
                $check_contact->channel     = $channel;
                $check_contact->datecreate  = $time;
                $check_contact->creby       = $creby;
                if (!empty($req->custom_field)) {
                    foreach ($req->custom_field as $key => $value) {
                        $key = str_replace('dynamic_', '', $key);
                        $check_field = CustomField::where('id',$key)->first();
                        if (!$check_field) {
                            return MyHelper::response(true,'Custom Field '.$key.' Do not exists', null,200);
                        }else{
                            $field[$key] = $value;
                        }
                    }
                    $custom_field = json_encode($field);    
                    $check_contact->custom_fields = $custom_field;
                }
                $check_contact->save();
                if(!$check_contact){
                    return MyHelper::response(false,'Updated Contact Failed', [],500);
                }
                return MyHelper::response(true,'Updated contact successfully', [],200);
            }  

        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],500);
        }
    }
    
    public function SubmitMerge($req,$id){
        $groupid    = auth::user()->groupid;
        $id_user    = auth::user()->id;
        $fullname   = auth::user()->fullname;
        $level      = auth::user()->level;

        $main_id     = $id;
        $array_sub   = $req['subId'];
        $get_content = $req['content']?:0;
        $get_event   = $req['eventId']?:0;
        //check ticket exist of main and sub
        $check_main=Ticket::where('id',$main_id)->first();
        if(!$check_main){
            return MyHelper::response(false,'The main ticket does not exits', [],404);
        }
        if(count($array_sub)==0){
            return MyHelper::response(false,'There is no ticket to merge', [],404);
        }
        foreach($array_sub as $id_sub){
            $check_sub=Ticket::where('id',$id_sub)->first();
            if(!$check_sub){
                return MyHelper::response(false,'The sub ticket with id #'.$id_sub.' does not exits', [],404);
            }
            if($check_sub['type']=='child'){
                return MyHelper::response(false,'You can not merge the child type ticket in ticket with id #'.$id_sub.'', [],500);
             }
         }
         $listSub=Ticket::whereIn('id',$array_sub)->get();
         if($check_main['type']=='child'){
            return MyHelper::response(false,'The main ticket can not be child type', [],404);
         }

         try{ 
            $array_id=$array_sub;
            // array_push($array_id,$main_id);
            $sql = "SELECT GROUP_CONCAT(user_id) AS id_follow FROM ticket_follow WHERE ticket_id IN(".$main_id.",".implode(',', $array_sub).") AND groupid = ".$groupid;
            $follow = Ticket::customQuery($sql);
            $follow = $follow['id_follow'];       
            $content_noti = 'Đã gộp các phiếu #'.implode(',#',$array_sub);
            $ins_notifi = array(
                'groupid'   => $groupid,
                'id_user'   => $check_main['assign_agent'],
                'id_team'   => $check_main['assign_team'],
                'id_follow' => $follow,
                'type'      => 'ticket',
                'ticket_id' => $check_main['id'],
                'title'     => '<b>'.$fullname.'</b> vừa cập nhật phiếu <b>#'.$check_main['ticket_id'].' - '.$check_main['title'].'</b>',
                'content'   => $content_noti,
                'channel'   => $check_main['channel'],
                'custom'    => json_encode(array('id' => $check_main['id'],'name' => $check_main['title'] , 'status' => $check_main['status'])),
                'view'      => $id_user,
                'del_agent' => $id_user,
            );
            Ticket::insert('notifications',$ins_notifi);
        } catch (Exception $e){
            return MyHelper::response(false,$e, [],500);
        }

        foreach ($listSub as $value) {
            if($value['type'] == 'parent'){
                DB::table('ticket_parent_child')
                ->where('groupid', $groupid)
                ->where('parent', $value['id'])
                ->update(['parent' => $main_id]);

                DB::table('ticket_'.$groupid)
                ->where('groupid', $groupid)
                ->where('id', $main_id)
                ->update(['type' => 'parent']);
            }elseif($value['type'] == 'child'){  
                $get_parent = (new Ticket)->show_by_id('ticket_parent_child',array('groupid' => $groupid, 'child' => $value['id']),'parent');
                $count_parent = Ticket::count_where('ticket_parent_child',array('parent' => $get_parent['parent']));
                if($count_parent < 2){
                    DB::table('ticket_'.$groupid)
                    ->where('groupid', $groupid)
                    ->where('id', $get_parent['parent'])
                    ->update(['type' => null]);
                }
                DB::table('ticket_parent_child')
                ->where('groupid', $groupid)
                ->where('child', $value['id'])
                ->delete();
            }
            //update social
            DB::table('social_activity_log')
            ->where('groupid', $groupid)
            ->where('ticket_id', $value['id'])
            ->update(['ticket_id' => $main_id]);

            DB::table('social_history')
            ->where('groupid', $groupid)
            ->where('ticket_id', $value['id'])
            ->update(['ticket_id' => $main_id]);

            DB::table('social_queues')
            ->where('groupid', $groupid)
            ->where('ticket_id', $value['id'])
            ->update(['ticket_id' => $main_id]);
        }
        if($get_content == 1){
            DB::table('ticket_detail_'.$groupid)
            ->where('groupid', $groupid)
            ->whereIn('ticket_id',$array_sub)
            ->update(['ticket_id' => $main_id]);
        }
        if($get_event == 1){
            $upd = array(
                'event_source'    => $check_main['requester_type'],
                'event_source_id' => $check_main['requester'],
                'ticket_id'     => $check_main['id'],
            );
            DB::table('event')
            ->where('groupid', $groupid)
            ->whereIn('ticket_id',$array_sub)
            ->update($upd);
        }
        

        if($check_main['status'] == 'new'){
            Ticket::where('id',$check_main['id'])->update(array('status' => 'open'));          
        }

        $ins = array(
            'groupid'        => $groupid,
            'ticket_id'      => $main_id,
            'content_system' => 'đã gộp các phiếu <b>#'.implode(',#', $array_sub).'</b>',
            'type'           => 'text',
            'createby'       => $id,
            'createby_level' => $level,
            'datecreate'     => time(),
            'private'        => 1,
        );
        Ticket::insert('ticket_detail_'.$groupid,$ins);
        Ticket::where('groupid',$groupid)->whereIn('id',$array_sub)->delete();
        return MyHelper::response(true,'Ticket merging successfully', [
            'main'=>['id' => $check_main['id']],
            'sub'=>['id_sub'=>$array_sub]
        ],200);
    }
    

}
<?php
namespace App\Models;

use Auth;
use DB;
use App\Models\Ticket;
use App\Http\Functions\MyHelper;
use App\Http\Functions\CheckTrigger;

trait TicketsTrait {

    public function create_or_update_ticket($req,$id_t = '')
    {     
        $groupid = auth::user()->groupid;
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
        $content  = $req['content'] ?? 'k có dữ liệu';
        $priority = $req['priority'] ?? 4;
        $category = $req['category'] ?? null; 
        $label    = $req['label'] ?? null;
        $status   = array_key_exists('status', $req) ? $req['status'] : 'new';  
        $private  = array_key_exists('private',$req) ? 1 : 0;    
        $channel  = 'api';    
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
                $check_contact = Contact::where('id',$contact_id)->count();
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
                return MyHelper::response(false,'Contact not found!', [],400); 
            }
            if ($id_t) {
                // Cập nhật phiếu
                $ticket = Ticket::where('id',$id_t)->first();
                $action = 'update';
                $message = 'vừa cập nhật phiếu';
                $assign_team    = array_key_exists('assign_team', $req) ? $req['assign_team'] : $ticket->assign_team;
                $assign_agent    = array_key_exists('assign_agent', $req) ? $req['assign_agent'] : $ticket->assign_agent;
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
                $assign_team  = array_key_exists('assign_team',$output) ? $output['assign_team'] : 0;
                $status       = array_key_exists('status',$output) ? $output['status'] : 'new';
                $label        = array_key_exists('label',$output) ? $output['label'] : null;
                $ticket = new Ticket;
                $ticket->requester      = $requester;
                $ticket->requester_type = 'contact';
                $ticket->assign_agent   = $assign_agent;
                $ticket->assign_team    = $assign_team;
                $ticket->createby       = $creby;
                $ticket->datecreate     = $time;
                $ticket->groupid        = $groupid;
                if ($groupid == '103') {
                    $ticket->mt_orderid     = array_key_exists('mt_orderid',$req) ? $req['mt_orderid'] : null;
                    $ticket->mt_productid   = array_key_exists('mt_productid',$req) ? $req['mt_productid'] : null;
                    $ticket->mt_qty         = array_key_exists('mt_qty',$req) ? $req['mt_qty'] : null;
                    $tdetail['mt_orderid'] = $req['mt_orderid'];
                    $tdetail['mt_productid'] = $req['mt_productid'];
                }
            }
            if (!empty($req['custom_field'])) {
                foreach ($req['custom_field'] as $key => $value) {
                    $key = str_replace('dynamic_', '', $key);
                    $check_field = CustomField::where('id',$key)->first();
                    if (!$check_field) {
                        return MyHelper::response(true,'Custom Field '.$key.' Do not exists', null,200);
                    }else{
                        $field[$key] = $value;
                    }
                }
                $custom_field = json_encode($field);
                $ticket->custom_fields  = $custom_field;
            }
            $ticket->title          = $title;
            $ticket->status         = $status;
            $ticket->channel        = $channel;
            $ticket->dateupdate     = $time;
            $ticket->priority       = $priority;
            $ticket->category       = $category;
            $ticket->label          = $label;
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
            if ($action == 'create') {
                return MyHelper::response(true,'Created Ticket Successfully', ['id' => $ticket_detail->id,'ticket_id' => "#".$ticket_detail->ticket_id],200);
            }else{
                return MyHelper::response(true,'Updated Ticket Successfully', [],200);
            }
        } catch (\Exception $ex){
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],500);

        }

    }
    function processingSLA_new($input = array(), $sla = array()){
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
                $count_con = count($conditions);
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
                            dd($resp);
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
        $html_msg = '';
        $ticket_detail = new TicketDetail;
        $check_tkd = $ticket_detail->where('ticket_id',$ticket_id)->orderBy('id','desc')->first();
        //Tạo chi tiết phiếu
        if (array_key_exists('private', $data)) {
            if (intval($data['private']) == 1) {
                $data['private'] = 1;
                $ms = 'private';
            }else{
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
                    $file->move(public_path().'/files/', $fname);  
                    array_push($ar, 
                        [
                        'file_original' => $file->getClientOriginalName(),
                        'file_extension' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'file_name' => $fname
                        ]
                    );
                }
                $ticket_detail->file_multiple = json_encode($ar);
            }else{
                $fname = md5($data['file'][0]->getClientOriginalName(). time()).'.'.$data['file'][0]->getClientOriginalExtension();
                $data['file'][0]->move(public_path().'/files/', $fname);  
                $ticket_detail->file_original = $data['file'][0]->getClientOriginalName();
                $ticket_detail->file_extension = $data['file'][0]->getClientOriginalExtension();
                $ticket_detail->file_size = $data['file'][0]->getSize();
                $ticket_detail->file_name = $fname;
            }
        }
        ///// end upload file //////
        $ticket_detail->title          = $data['title'] ?? $ticket->title;
        $ticket_detail->ticket_id      = $ticket->id;
        $ticket_detail->groupid        = $ticket->groupid;
        $ticket_detail->channel        = $ticket->channel;
        $ticket_detail->private        = $data['private'];
        $ticket_detail->type           = 'text';
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
            if (array_key_exists('mt_orderid', $data) && array_key_exists('mt_productid', $data)) {
                $html_msg .= '<div><i>Đã tạo đơn hàng '.$data['mt_orderid'].' thành công</i></div>';
            }
            if (array_key_exists('content', $data)) {
                $html_msg .= $data['content'];
            }
        }
        $ticket_detail->content_system = $html_msg;
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
                'groupid'   => auth::user()->groupid,
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
}
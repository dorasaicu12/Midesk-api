<?php
namespace App\Http\Functions;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Ticket;
use Auth;

class CheckTrigger
{

    static function check_trigger_ticket_roundrobin($input){
        $trigger = Ticket::show_order_where('trigger',['sort DESC','id DESC'],"channel = 'ticket' AND groupid = ".$input['groupid']." AND public = 1",'content,name');
        $output = CheckTrigger::check_trigger_ticket($input,$trigger);


        //default : round robin | ring all đang dùng RR
        //nếu đổ vào nhóm, check các agent trong nhóm có online thì đỗ vào, k ai online thì đỗ vào nhóm luôn, rồi a click vào thì assign cho người đó   

        if(@!$output){
            //nếu không thỏa các điều kiện nào thì chỉ địn cho nhóm default
            // $default = $this->model->show_by_id('team',array('groupid' => $input['groupid'], 'type' => 'default'),'team_id');            
            // $output['assign_team'] = $default['team_id'];

            //chuyển cho agent (admin)            
            $default = Ticket::show_by_id('table_users',"groupid = ".$input['groupid']." AND level = 'groupadmin'",'id'); 
            $output['assign_agent'] = $default['id'];
            return $output;
        }


        if(isset($output['assign_agent'])){
            return $output;
        }
    
        if(@$output['assign_team']){
            $sql = "SELECT s.team_id,s.agent_id, flag_facebook, u.status_chat 
                    FROM team_staff s
                    LEFT JOIN table_users u ON u.id = s.agent_id
                    WHERE s.team_id = ".$output['assign_team']." AND s.groupid = ".$input['groupid'];
            $rr_agent = Ticket::customQuery($sql,true);

            $total_rr_agent = count($rr_agent) - 1;
            $flag_agent     = 0;
            $next_agent     = '';
            $tmp_flag = 0;

            $online_array = array();
            foreach ($rr_agent as $on) {
                if($on['status_chat'] == 'Online') $online_array[] = $on;
            }


            $count_on = count($online_array);   
            //check những thằng online
            if($count_on > 0){
                foreach ($online_array as $key => $rr) {
                    if($rr['flag_facebook'] == 1 && $key == ($count_on - 1)){ //thằng assign trước đó là cúi => lấy thằng đầu
                        $next_agent = $online_array[0];
                        $flag_agent = 1;                
                        break;
                    }elseif($rr['flag_facebook'] == 1){
                        $next_agent = $online_array[$key+1];
                        $flag_agent = 1;
                        break;
                    }else{
                        $tmp_flag++;
                    }
                }
            }           

            //trường hợp chưa mới chưa được assign lần nào
            if($tmp_flag == count($rr_agent)){
                foreach ($rr_agent as $value) {
                    if($value['status_chat'] == 'Online'){
                        $next_agent = $value;
                        $flag_agent = 1;
                        break;
                    }
                }
            }

            if($flag_agent == 0){
                foreach ($rr_agent as $key => $rr) {
                    if($rr['flag_facebook'] == 1 && $key == $total_rr_agent){ //thằng assign trước đó là cúi => lấy thằng đầu
                        $next_agent = $rr_agent[0];
                        $flag_agent = 1;                
                        break;                  
                    }elseif($rr['flag_facebook'] == 1){                 
                        $next_agent = $rr_agent[$key+1];
                        $flag_agent = 1;
                        break;
                    }
                }
            }

            

            if($flag_agent==0){
                $output['assign_team'] = $output['assign_team'];
                $next_agent = $rr_agent[0];
                Ticket::update_team_staff($next_agent['agent_id'],$next_agent['team_id'],$input['groupid']);
                $output['assign_agent'] = $next_agent['agent_id'];
            }else{
                Ticket::update_team_staff($next_agent['agent_id'],$next_agent['team_id'],$input['groupid']);
                $output['assign_agent'] = $next_agent['agent_id'];
            }           
            return $output;
        }

        if(@!$output['assign_agent']){
            $default = Ticket::show_by_id('table_users',"groupid = ".$input['groupid']." AND level = 'groupadmin'",'id'); 
            $output['assign_agent'] = $default['id'];
            return $output;
        }
    }
    
    static function check_trigger_ticket($input,$trigger){
        $groupid  = @$input['groupid']?:0;

        $input_channel  = @$input['channel']?:'';
        $input_timework = @$input['timework']?:'';
        //email
        $input_email_emailsystem   = @$input['email_email_system']?:'';
        $input_email_emailcustomer = @$input['email_email_customer']?:'';
        $input_email_object        = @$input['email_object']?:'';
        $input_email_content       = @$input['email_content']?:'';
        //facebook
        $input_facebook_page    = @$input['facebook_page']?:''; //id page
        $input_facebook_post    = @$input['facebook_post']?:''; //id_post
        $input_facebook_type    = @$input['facebook_type']?:''; //inbox/comment
        $input_facebook_content = @$input['facebook_content']?:'';
        $input_facebook_uid     = @$input['facebook_uid']?:'';
        //chat
        $input_chat_service = @$input['chat_service']?:'';
        $input_chat_source  = @$input['chat_source']?:'';

        // $input_customer      = @$input['id_customer']?:array();
        // $input_category      = @$input['category']?:array();

        //webform
        $input_webform_website      = @$input['webform_website']?:'';
        $input_webform_email        = @$input['webform_email']?:'';
        $input_webform_content      = @$input['webform_content']?:'';
        $input_webform_link_keyword = @$input['webform_link_keyword']?:'';

        $output = array();
        foreach ($trigger as $key => $value) {
            $action_check = false;
            $content =  json_decode(@$value['content'],true);
            if (!is_array($content) && !($content instanceof Traversable)) $content = array();
            //CHECK CONDITONS
            if($content['operator'] == 'all'){ //AND
                $count_con = count($content['conditions']);
                $count_tmp = 0;
                // print_r($content);
                // echo $input_email_emailcustomer;
                foreach ($content['conditions'] as $cond) {
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
                        case 'timework':
                            $outtime  = 0;                             
                            $timework = Ticket::show_by_id('time_work',"groupid = $groupid AND id=".$cond['value'],'detail,holiday,full_time');
                            
                            if($timework['full_time'] != 1){                                
                                $data_arr = json_decode($timework['detail'],true);
                                $info = $data_arr[date('D',$input_timework)];
                                
                                if($info['active']==1){
                                    $be = strtotime($info['begin'].' '.$info['begin_t']);
                                    $en = strtotime($info['end'].' '.$info['end_t']);

                                    if ($input_timework < $be || $input_timework > $en){
                                        $outtime = 1;
                                        goto endcheckOT; //nếu thỏa thì nhảy tới endcheckOT: luôn
                                    }
                                }else{
                                    $outtime = 1;
                                    goto endcheckOT; //nếu thỏa thì nhảy tới endcheckOT: luôn
                                }
                            }
                            endcheckOT://bước nhảy  
                            if($cond['operator'] == 'is'){
                                if($outtime == 0){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is')
                                 if($outtime == 1){
                                    $count_tmp++;
                                }
                            break;  
                        //====================== EMAIL
                        case 'email_email_system':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_email_emailsystem,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_email_emailsystem,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;
                        case 'email_email_customer':
                            // echo 'vo';
                            if($cond['operator'] == 'is'){
                                 // echo 'day';
                                if(in_array($input_email_emailcustomer,$cond['value'])){
                                    // echo 'nè';
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_email_emailcustomer,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;
                        case 'email_object':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_email_object))) {
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_email_object))) {
                                    $count_tmp++;
                                }
                            }
                            break; 
                        case 'email_content':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_email_content))) {
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_email_content))) {
                                    $count_tmp++;
                                }
                            }
                            break;        
                        //====================== FACEEBOOK    
                        case 'facebook_page':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_page))) {
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_page))) {
                                    $count_tmp++;
                                }
                            }
                            break;
                        case 'facebook_post':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_post))) {
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_post))) {
                                    $count_tmp++;
                                }
                            }
                            break; 
                        case 'facebook_inbox':case 'facebook_comment':
                            if($input_facebook_type == str_replace('facebook_','',$cond['condition'])){
                                $count_tmp++;
                            }
                            break;           
                        case 'facebook_keyword':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_content))) {
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_content))) {
                                    $count_tmp++;
                                }
                            }
                            break;
                        case 'facebook_phone': 
                            $is_phone = 0;
                            preg_match_all("!\d+!", str_replace(str_split('.-'), '', $input_facebook_content), $myphone);
                            foreach ($myphone[0] as $phone) {
                                if(strlen($phone) >= 9 && strlen($phone) <= 12){
                                    if($phone[0] == '0' || ($phone[0] == '8' && $phone[1] == '4')){
                                        $count_tmp++;
                                        break;
                                    }
                                }
                            }
                            break;
                        case 'facebook_uid':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_facebook_uid,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_facebook_uid,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break;            
                        //====================== CHAT
                        case 'chat_service':
                            if($cond['operator'] == 'is'){
                                if($input_chat_service == $cond['value']){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if($input_chat_service != $cond['value']){
                                    $count_tmp++;
                                }
                            }
                            break; 
                        case 'chat_source':          
                            if($cond['operator'] == 'is'){
                                if(in_array($input_chat_source,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_chat_source,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break; 
                       
                        //====================== WEBFORM
                        case 'webform_website':
                            if($cond['operator'] == 'is'){                               
                                // $pattern = '/:\/\/(.+?)\//';
                                // preg_match($pattern, $input_webform_website, $matches);

                                $list = explode('/', $input_webform_website);
                                $website = @$list[2]?:'';
                                if(in_array($website,$cond['value'])){
                                   $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                $list = explode('/', $input_webform_website);
                                $website = @$list[2]?:'';
                                if(!in_array($website,$cond['value'])){
                                   $count_tmp++;
                                }
                            }
                            break;
                        case 'webform_email':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_webform_email,$cond['value'])){
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_webform_email,$cond['value'])){
                                    $count_tmp++;
                                }
                            }
                            break; 
                        case 'webform_content':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_webform_content))) {
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_webform_content))) {
                                    $count_tmp++;
                                }
                            }
                            break;  
                        case 'webform_link_keyword':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_webform_link_keyword))) {
                                    $count_tmp++;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_webform_link_keyword))) {
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
            }else{
                $count_tmp = 0;
                foreach ($content['conditions']as $cond) {
                    switch ($cond['condition']) {
                        case 'channel':                         
                            if($cond['operator'] == 'is'){
                                if(in_array($input_channel,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_channel,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;
                        case 'timework':
                            $outtime  = 0;                             
                            $timework = Ticket::show_by_id('time_work',"groupid = $groupid AND id = ".$cond['value'],'detail,holiday,full_time');
                            
                            if($timework['full_time'] != 1){                                
                                $data_arr = json_decode($timework['detail'],true);
                                $info = $data_arr[date('D',$input_timework)];
                                
                                if($info['active']==1){
                                    $be = strtotime($info['begin'].' '.$info['begin_t']);
                                    $en = strtotime($info['end'].' '.$info['end_t']);

                                    if ($input_timework < $be || $input_timework > $en){
                                        $outtime = 1;
                                        goto gotoCheckOT; //nếu thỏa thì nhảy tới gotoCheckOT: luôn
                                    }
                                }else{
                                    $outtime = 1;
                                    goto gotoCheckOT; //nếu thỏa thì nhảy tới gotoCheckOT: luôn
                                }
                            }
                            gotoCheckOT://bước nhảy  
                            if($cond['operator'] == 'is'){
                                if($outtime == 0){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is')
                                 if($outtime == 1){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            break;  
                        //====================== EMAIL
                        case 'email_system':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_email_emailsystem,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_email_emailsystem,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;    
                        case 'email_customer':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_email_emailcustomer,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_email_emailcustomer,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;
                        case 'email_object':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_email_object))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_email_object))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break; 
                        case 'email_content':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_email_content))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_email_content))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;
                        //====================== FACEEBOOK  
                        case 'facebook_page':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_page))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_page))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;
                        case 'facebook_post':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_post))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_post))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break; 
                        case 'facebook_inbox':case 'facebook_comment':
                            if($input_facebook_type == str_replace('facebook_','',$cond['condition'])){
                                $count_tmp++;
                                goto end_check_condition_any;
                            }
                            break;  
                        case 'facebook_keyword':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_content))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_facebook_content))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;
                        case 'facebook_phone': 
                            $is_phone = 0;
                            preg_match_all("!\d+!", str_replace(str_split('.-'), '', $input_facebook_content), $myphone);
                            foreach ($myphone[0] as $phone) {
                                if(strlen($phone) >= 9 && strlen($phone) <= 12){
                                    if($phone[0] == '0' || ($phone[0] == '8' && $phone[1] == '4')){
                                        $count_tmp++;
                                        goto end_check_condition_any;
                                    }
                                }
                            }
                            break;
                        case 'facebook_uid':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_facebook_uid,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_facebook_uid,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;         
                        //====================== CHAT
                        case 'chat_service':
                            if($cond['operator'] == 'is'){
                                if($input_chat_service == $cond['value']){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if($input_chat_service != $cond['value']){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;  
                        case 'chat_source':       
                            if($cond['operator'] == 'is'){
                                if(in_array($input_chat_source,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_chat_source,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;        
                        
                        //====================== WEBFORM
                        case 'webform_website':
                            if($cond['operator'] == 'is'){  
                                $list = explode('/', $input_webform_website);
                                $website = @$list[2]?:'';
                                if(in_array($website,$cond['value'])){
                                   $count_tmp++;
                                   goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                $list = explode('/', $input_webform_website);
                                $website = @$list[2]?:'';
                                if(!in_array($website,$cond['value'])){
                                   $count_tmp++;
                                   goto end_check_condition_any;
                                }
                            }
                            break;
                        case 'webform_email':
                            if($cond['operator'] == 'is'){
                                if(in_array($input_webform_email,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!in_array($input_webform_email,$cond['value'])){
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break; 
                        case 'webform_content':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_webform_content))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_webform_content))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;       
                        case 'webform_link_keyword':
                            if($cond['operator'] == 'is'){
                                if(preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_webform_link_keyword))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }elseif($cond['operator'] == 'not_is'){
                                if(!preg_match(strtolower('['.implode('|', $cond['value']).']'), strtolower($input_webform_link_keyword))) {
                                    $count_tmp++;
                                    goto end_check_condition_any;
                                }
                            }
                            break;     
                        default:
                            break;
                    }   
                }
                end_check_condition_any:
                if($count_tmp > 0){
                    $action_check = true;
                }
            }
            //ACTION
            if($action_check){
                foreach ($content['actions'] as $act) { 
                    $output[$act['action']] = $act['value'];
                }
                return $output;
            }
        }
        return $output;
    }
}
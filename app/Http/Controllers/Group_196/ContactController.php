<?php

namespace App\Http\Controllers\Group_196;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Functions\MyHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\CustomField;
use Carbon\Carbon;
use App\Http\Requests\Group_196\ContactRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Auth;
use DB;

/**
 * @group  Contact Management
 *
 * APIs for managing contact
 */
class ContactController extends Controller
{
    
    /**
     * Get All Contact
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
                    "id": 1184,
                    "contact_id": "MCT000389",
                    "groupid": 2,
                    "firstname": null,
                    "lastname": null,
                    "fullname": "Sendgrid Renewal Team",
                    "phone": "",
                    "email": "test@test.com"
                },{
                    "id": 1183,
                    "contact_id": "MCT000380",
                    "groupid": 2,
                    "firstname": null,
                    "lastname": null,
                    "fullname": "Sendgrid Renewal Team",
                    "phone": "",
                    "email": "test@test.com"
            }],
            "first_page_url": "http://api-prod2021.midesk.vn/api/Group_196/contact?limit=5&key_search=fullname%3Aand%2Caddress%3Aor&q=new&order_by=id%3Aasc%2Ccontact_id%3Adesc&fields=contact_id%2Cgroupid%2Cfirstname%2Clastname%2Cfullname%2Cphone%2Cemail&page=1",
            "from": 1,
            "last_page": 1,
            "last_page_url": "http://api-prod2021.midesk.vn/api/Group_196/contact?limit=5&key_search=fullname%3Aand%2Caddress%3Aor&q=new&order_by=id%3Aasc%2Ccontact_id%3Adesc&fields=contact_id%2Cgroupid%2Cfirstname%2Clastname%2Cfullname%2Cphone%2Cemail&page=1",
            "next_page_url": null,
            "path": "http://api-prod2021.midesk.vn/api/Group_196/contact",
            "per_page": "5",
            "prev_page_url": null,
            "to": 2,
            "total": 2
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
        $contacts = new Contact;
        $contacts = $contacts->getDefault($req);
        return MyHelper::response(true,'Successfully',$contacts,200);
    }

    /**
     * Show A Contact.
     *
     * @bodyParam contact int required id of the contact.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Successfully",
     *   "data": {
            "fullname":"Hao Ngo Dev",
            "phone": "0971234567",
            "email": "haongodev@gmail.com",
            "address": "Lạc Long Quân - Tân Bình",
            "gender": "male",
            "custom_field":{
                "dynamic_1":"Field 1",
                "dynamic_2":"Field 2"
            }
     *   }
     * }
     * @response 404 {
     *   "status": false,
     *   "message": "Resource Not Found",
     *   "data": {}
     * }
     */
    public function show($id)
    {
        $contact = (new Contact)->ShowOne($id);
        if (!$contact) {            
            Log::channel('contact_history')->info('Contact notfound',['id'=>$id]);
            return MyHelper::response(false,'Contact not found',[],200);
        }else{
            return MyHelper::response(true,'Successfully',$contact,200);
        }
    }
    
    /**
     * Create A Contact
     *
     * @bodyParam fullname string required full name contact.
     * @bodyParam phone string required phone Example: 0123456789.
     * @bodyParam email string required email. Example: admin@gmail.com
     * @bodyParam address string optional address.
     * @bodyParam gender string optional gender 'male' or 'female'. Example: male

     
     * @response 200 {
     *   "status": true,
     *   "message": "Create Contact Successfully",
     *   "data": {
     *       "id": "{$id}",
     *       "contact_id": "{$contact_id}"
     *   }
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Resource not found"
     * }

     * @response  400 {
     *   "status": false,
     *   "message": "fullname is require",
     *   "data": {}
     * }
     */

    public function store(ContactRequest $req)
    {
        $groupid = auth::user()->groupid;
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
            $contact = new Contact;

            if ($groupid == '196') {
                $contact_id = 'ext_contact_id';
                $ext_contact_id = $req->ext_contact_id;
                if(!empty($phone) && !empty($ext_contact_id)){
                    $check_contact = $contact->where(function($q) use ($phone, $ext_contact_id) {
                            $q->Where('ext_contact_id', $ext_contact_id)->orwhere('phone', $phone);
                        })->first();
                }elseif(empty($phone)){
                    $check_contact = $contact->where('ext_contact_id',$ext_contact_id)
                        ->first();
                }else{
                    $check_contact = $contact->where('phone',$phone)
                        ->first();
                }
            }else{
                $contact_id = 'contact_id';
                if(!empty($email) && !empty($phone)){
                    $check_contact = $contact->where(function($q) use ($phone, $email) {
                        $q->where('phone', $phone)->orWhere('email', $email);
                    })->first();
                }elseif(empty($phone)){
                    $check_contact = $contact->where('email',$email)->first();
                }else{
                    $check_contact = $contact->where('phone',$phone)->first();
                }
            }


            DB::commit();

            //Thêm mới Contact

            if(!$check_contact){
            	if ($groupid == '196') {
	            	$contact->ext_contact_id = $req->ext_contact_id;
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
            	}                 
                $contact->address     	= $address;
                $contact->groupid   	= $groupid;
                $contact->fullname   	= $fullname;
                $contact->phone    		= $phone;
                $contact->email      	= $email;                  
                $contact->gender     	= $gender;                  
                $contact->channel    	= $channel;
                $contact->datecreate 	= $time;
                $contact->creby      	= $creby;
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

                $new_contact = Contact::select($contact_id)->find($id);                
                Log::channel('contact_history')->info('Create contact successfully',['id'=>$id,'request'=>$req->all()]);
                return MyHelper::response(true,'Create contact successfully', ['id' => $id,'contact_id' => $new_contact->$contact_id],200);
            }else{
            	if ($groupid == '196') {
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
                    $check_contact->address    	= $address;
	                $check_contact->groupid   	= $groupid;
	                $check_contact->fullname   	= $fullname;
	                $check_contact->phone    	= $phone;
	                $check_contact->email      	= $email;                  
	                $check_contact->gender     	= $gender;                  
	                $check_contact->channel    	= $channel;
	                $check_contact->datecreate 	= $time;
	                $check_contact->creby      	= $creby;

	                if($check_contact->save()){ 
                        Log::channel('contact_history')->info('Updated contact successfully',['req'=>$req->all()]);
	                    return MyHelper::response(false,'Updated Contact successfully', [],200);
	                }
            	}        
                return MyHelper::response(true,'Contact already exists', ['id' => $check_contact->id,'contact_id' => $check_contact->$contact_id],200);
            }  

        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],500);
        }
    }
    /**
     * Update A Contact
     *
     * @urlParam  contact required The ID of the contact. Example: 1
     * @bodyParam fullname string required full name contact.
     * @bodyParam phone string required phone Example: 0123456789.
     * @bodyParam email string required email. Example: admin@gmail.com
     * @bodyParam address string optional address.
     * @bodyParam gender string optional gender.

     
     * @response 200 {
     *   "status": true,
     *   "message": "Update Contact Successfully",
     *   "data": []
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Resource not found"
     * }

     * @response  400 {
     *   "status": false,
     *   "message": "fullname is require",
     *   "data": []
     * }
     */
    public function update(ContactRequest $req,$id)
    {
        $groupid = auth::user()->groupid;
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
            $contact = new Contact;
            if ($groupid == '196') {
                $contact_id = 'ext_contact_id';
                $check_contact = $contact->where(function($q) use ($id) {
                        $q->Where('ext_contact_id', $id)->orwhere('phone', $id);
                    })->first();
            }else{
                $contact_id = 'contact_id';
                if(!empty($email) && !empty($phone)){
                    $check_contact = $contact->where('id',$id)
                        ->where(function($q) use ($phone, $email) {
                            $q->where('phone', $phone)->orWhere('email', $email);
                        })->first();
                }elseif(empty($phone)){
                    $check_contact = $contact->where('id',$id)
                        ->where('email',$email)
                        ->first();
                }else{
                    $check_contact = $contact->where('id',$id)
                        ->where('phone',$phone)
                        ->first();
                }
            }


            DB::commit();

            //Cập nhật Contact
            if(!$check_contact){
                return MyHelper::response(true,'Contact Not found', [],400);
            }else{
            	if ($groupid == '196') {
                    if ($req->ext_contact_id) {                    
                        $check_contact->ext_contact_id = $req->ext_contact_id;
                    }
                    if (rtrim($req->phone_other) != rtrim($phone)) {
                        $contact->phone_other = $req->phone_other;
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
            	}
                $check_contact->fullname   	= $fullname;
                $check_contact->phone    	= $phone;
                $check_contact->email      	= $email;
                $check_contact->address   	= $address;                  
                $check_contact->gender     	= $gender;                  
                $check_contact->channel    	= $channel;
                $check_contact->datecreate 	= $time;
                $check_contact->creby      	= $creby;
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

}

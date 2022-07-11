<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Functions\MyHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\CustomField;
use App\Models\ModelsTrait;
use Carbon\Carbon;
use App\Http\Requests\v3\ContactRequest;
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
    use ModelsTrait;

    /**
    * @OA\Get(
    *     path="/api/v3/contact",
    *     tags={"Contact"},
    *     summary="Get list contact",
    *     description="Get list contact with param",
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
    *         description="Condition to find contact ({$key}={$value})",
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
    *                   @OA\Property(property="contact_id",type="object", example="1"),
    *                   @OA\Property(property="firstname",type="object", example="văn A"),
    *                   @OA\Property(property="lastname",type="object", example="Nguyễn"),
    *                   @OA\Property(property="phone",type="object", example="0987654321"),
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
    *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/contact"),
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
        $contacts = new Contact;
        $contacts = $contacts->getDefault($req);
        return MyHelper::response(true,'Successfully',$contacts,200);
    }

    /**
    * @OA\Get(
    *     path="/api/v3/contact/{contactId}",
    *     tags={"Contact"},
    *     summary="Find the contact by ID",
    *     description="Will be return a single contact",
    *     operationId="show",
    *     @OA\Parameter(
    *         name="contactId",
    *         in="path",
    *         description="ID of contact",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )    
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="true"),
    *           @OA\Property(property="message", type="string", example="Successfully"),
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="string", example="1"),
    *             @OA\Property(property="contact_id",type="string", example="1"),
    *             @OA\Property(property="fullname",type="string", example="Nguyễn văn A"),
    *             @OA\Property(property="phone",type="string", example="0987654321"),
    *             @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
    *             @OA\Property(property="custom_field",type="array", 
    *               @OA\Items(type="object",
    *                 @OA\Property(property="dynamic_1",type="string", example="value dynamic 1"),
    *                 @OA\Property(property="dynamic_2",type="string", example="value dynamic 2"),
    *               ),
    *             ),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Contact not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Contact not found"),
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
        $contact = (new Contact)->ShowOne($id);
        if (!$contact) {            
            Log::channel('contact_history')->info('Contact notfound',['id'=>$id]);
            return MyHelper::response(false,'Contact not found',[],200);
        }else{
            return MyHelper::response(true,'Successfully',$contact,200);
        }
    }

    /**
    * @OA\POST(
    *     path="/api/v3/contact",
    *     tags={"Contact"},
    *     summary="Create the contact with json form",
    *     description="Create contact form",
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
    *         response=200,
    *         description="Create Contact Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="true"),
    *           @OA\Property(property="message", type="string", example="Create Contact Successfully"),
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="string", example="1"),
    *             @OA\Property(property="contact_id",type="string", example="1"),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Create failed",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="true"),
    *           @OA\Property(property="message", type="string", example="Fullname is require"),
    *           @OA\Property(property="data",type="string", example="[]"),
    *         )
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
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

            $check_contact = Contact::checkContact($phone,$email);

            DB::commit();

            //Thêm mới Contact
              if($fullname==""){
                return MyHelper::response(true,'fullname field is required', [],200);
              }else{


                if(!$check_contact){    
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
    
                    $new_contact = Contact::select('contact_id')->find($id);                
                    Log::channel('contact_history')->info('Create contact successfully',['id'=>$id,'request'=>$req->all()]);
                    return MyHelper::response(true,'Create contact successfully', ['id' => $id,'contact_id' => $new_contact->contact_id],200);
                }else{   
                    return MyHelper::response(true,'Contact already exists', ['id' => $check_contact->id,'contact_id' => $check_contact->contact_id],200);
                }

              }


            
            
            



        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],500);
        }
    }
    /**
    * @OA\Put(
    *     path="/api/v3/contact/{contactId}",
    *     tags={"Contact"},
    *     summary="Update the contact by ID",
    *     description="Update a contact with input",
    *     operationId="update",
    *     @OA\Parameter(
    *         name="contactId",
    *         in="path",
    *         description="ID of contact",
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
    *         required={"fullname","phone","email"},
    *         @OA\Property(property="fullname",type="string", example="Phiếu khiếu nại 2"),
    *         @OA\Property(property="phone",type="string", example="Nội dung phiếu số 1"),
    *         @OA\Property(property="email",type="string", example="Facebook"),
    *         @OA\Property(property="address", type="string", example="1"),
    *         @OA\Property(property="gender", type="string", example="1"),
    *         @OA\Property(property="custom_field",type="array", 
    *           @OA\Items(type="object",
    *             @OA\Property(property="dynamic_1",type="string", example="value dynamic 1"),
    *             @OA\Property(property="dynamic_2",type="string", example="value dynamic 2"),
    *           ),
    *         ),
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Update successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Update contact successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Update failed",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Email required"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Will be return contact not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Contact not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
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
            
            $check_contact = Contact::checkContact($phone,$email,$id);

            DB::commit();

            //Cập nhật Contact
            if(!$check_contact){
                return MyHelper::response(true,'Contact Not found', [],400);
            }else{
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
                return MyHelper::response(true,'Updated contact successfully', [$check_contact],200);
            }  

        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],500);
        }
    }

    
    /**
    * @OA\Delete(
    *     path="/api/v3/contact/{contactId}",
    *     tags={"Contact"},
    *     summary="Deletes a contact",
    *     operationId="destroy",
    *     @OA\Parameter(
    *         name="contactId",
    *         in="path",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Delete contact successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Delete contact successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="contact not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Contact not found"),
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
        $contact = (new Contact)->ShowOne($id);
        if ($contact) {            
            
            // $contact->update(['is_delete' => Contact::DELETED,'is_delete_date' =>date('Y-m-d H:i:s'),'is_delete_creby' => auth::user()->id]);
            DB::table('contact_2')->where('id', $id)->delete();
            return MyHelper::response(true,'Delete Contact Successfully', [],200);
        }else{
            return MyHelper::response(false,'Contact Not Found', [],404);
        }

        
    }

}

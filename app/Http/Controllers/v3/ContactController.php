<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Functions\MyHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\CustomField;
use App\Traits\ProcessTraits;
use Carbon\Carbon;
use App\Http\Requests\ContactRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Auth;
use DB;
use App\Models\actionLog;
use App\Models\customerContactRelation;

/**
 * @group  Contact Management
 *
 * APIs for managing contact
 */
class ContactController extends Controller
{
    use ProcessTraits;

    /**
    * @OA\Get(
    *     path="/api/v3/contact",
    *     tags={"Contact"},
    *     summary="Get list contact",
    *     description="<h2>This API will Get list contact with condition below</h2>",
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
    *         example="firstname<=>Nhỡ",
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
    *         example="fullname,phone,email",
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
    *                   @OA\Property(property="contact_id",type="string", example="1"),
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
        $contacts = (new Contact)->getDefault($req);
        return MyHelper::response(true,'Successfully',$contacts,200);
    }

    /**
    * @OA\Get(
    *     path="/api/v3/contact/{contactId}",
    *     tags={"Contact"},
    *     summary="Find contact by contactId",
    *     description="<h2>This API will find contact by {contactId} and return only a single record</h2>",
    *     operationId="show",
    *     @OA\Parameter(
    *         name="contactId",
    *         in="path",
    *         description="<h4>This is the id of the contact you are looking for</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         example=1,
    *         required=true,
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
    *             @OA\Property(property="firstname",type="string", example="Nguyễn"),
    *             @OA\Property(property="lastname",type="string", example="văn A"),
    *             @OA\Property(property="fullname",type="string", example="Nguyễn văn A"),
    *             @OA\Property(property="phone",type="string", example="0987654321"),
    *             @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
    *             @OA\Property(property="gender",type="string", example="null"),
    *             @OA\Property(property="province",type="string", example="null"),
    *             @OA\Property(property="district",type="string", example="null"),
    *             @OA\Property(property="birthday",type="string", example="null"),
    *             @OA\Property(property="address",type="string", example="null"),
    *           ),
    *         )
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
    *     }
    * )
    */
    public function show($id)
    {
        $contact = (new Contact)->ShowOne($id);
        if (!$contact) {            
            Log::channel('contact_history')->info('Contact notfound',['id'=>$id]);
            return MyHelper::response(false,'Contact not found',[],404);
        }else{
            return MyHelper::response(true,'Successfully',$contact,200);
        }
        return MyHelper::response(true,'Successfully',$contact,200);    
    }

    /**
    * @OA\POST(
    *     path="/api/v3/contact",
    *     tags={"Contact"},
    *     summary="Create a contact",
    *     description="<h2>This API will Create a contact with json form below</h2><br><code>Press try it out button to modified</code>",
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
                    <th>fullname</th>
                    <td>This is full name of contact</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>email</th>
                    <td>This is email of contact</td>
                    <td>true if without phone</td>
                </tr>
                <tr>
                    <th>phone</th>
                    <td>This is phone number of contact</td>
                    <td>true if without email</td>
                </tr>
                <tr>
                    <th>address</th>
                    <td>This is address of contact</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>gender</th>
                    <td>This is gender of contact</td>
                    <td>false</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       @OA\JsonContent(
    *         required={"fullname","phone"},
    *         @OA\Property(property="fullname", type="string", example="Nguyễn văn A"),
    *         @OA\Property(property="email", type="string", example="acb@xyz"),
    *         @OA\Property(property="phone", type="string", example="0123456789"),
    *         @OA\Property(property="address", type="string", example="123/321/HCM"),
    *         @OA\Property(property="gender", type="string", example="female"),
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
    *         response=422,
    *         description="Create Contact failed",
    *         @OA\JsonContent(
    *           @OA\Property(property="message", type="string", example="The given data was invalid"),
    *           @OA\Property(property="errors",type="object",
    *             @OA\Property(property="fullname",type="array", 
    *               @OA\Items(type="string", example="the fullname field is required")
    *             ),
    *           )
    *         ),
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

            $check_contact = (new Contact)->checkContact($phone,$email);
            DB::commit();

            //Thêm mới Contact

            if(!$check_contact){    
                $contact = new Contact();
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

        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],500);
        }
    }
    /**
    * @OA\Put(
    *     path="/api/v3/contact/{$contactId}",
    *     tags={"Contact"},
    *     summary="Update contact by contactId",
    *     description="<h2>This API will update a contact by contactId and the value json form below</h2><br><code>Press try it out button to modified</code>",
    *     operationId="update",
    *     @OA\Parameter(
    *         name="contactId",
    *         in="path",
    *         example=1,
    *         description="<h4>This is the id of the contact you need update</h4>
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
                    <th>fullname</th>
                    <td>This is full name of contact</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>email</th>
                    <td>This is email of contact</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>phone</th>
                    <td>This is phone number of contact</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>address</th>
                    <td>This is address of contact</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>gender</th>
                    <td>This is gender of contact</td>
                    <td>false</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       @OA\JsonContent(
    *         required={"fullname","phone","email"},
    *         @OA\Property(property="fullname",type="string", example="Ngô văn B"),
    *         @OA\Property(property="phone",type="string", example="0987654321"),
    *         @OA\Property(property="email",type="string", example="abc@xyz123"),
    *         @OA\Property(property="address", type="string", example="123/321/HCM"),
    *         @OA\Property(property="gender", type="string", example="male"),
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
    public function update(Request $request,$id)
    {
        $groupid = auth::user()->groupid;
        $creby   = auth::user()->id;
        $time     = time();
        $field = [];
        $phone = $request->phone ?? null;
        $email = $request->email ?? null;

        $channel_list  = ['facebook', 'zalo', 'webform', 'email', 'web', 'api'];
        $channel = 'api';
        if (array_key_exists('channel', $request)) {
            if (in_array($request->channel, $channel_list)) {
                $channel = $request->channel;
            }
        }
        DB::beginTransaction();
        try {
            //Kiểm tra tồn tại contact hay không
            $check_contact = (new Contact)->ShowOne($id);
            DB::commit();

            //Cập nhật Contact
            if(!$check_contact){
                return MyHelper::response(false,'Contact Not found', [],400);
            }else{

                $request = array_filter($request->all());   
                $request['channel']    	= $channel;
                $request['datecreate'] 	= $time;
                $request['creby']      	= $creby;
                if (!empty($request->custom_field)) {
                    foreach ($request->custom_field as $key => $value) {
                        $key = str_replace('dynamic_', '', $key);
                        $check_field = CustomField::where('id',$key)->first();
                        if (!$check_field) {
                            return MyHelper::response(true,'Custom Field '.$key.' Do not exists', null,200);
                        }else{
                            $field[$key] = $value;
                        }
                    }
                    $custom_field = json_encode($field);    
                    $request['custom_fields'] = $custom_field;
                }
                $check_contact->update($request);
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

    
    /**
    * @OA\Delete(
    *     path="/api/v3/contact/{contactId}",
    *     tags={"Contact"},
    *     summary="Delete a contact by contactId",
    *     description="<h2>This API will delete a contact by contactId</h2>",
    *     operationId="destroy",
    *     @OA\Parameter(
    *         name="contactId",
    *         in="path",
    *         example=1,
    *         description="<h4>This is the id of the contact you need delete</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         required=true,
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
    *         description="Contact not found",
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

        //check contact
        if (!$contact) {
            return MyHelper::response(false,'Contact Not Found', [],404);
        }else{
            $groupid = auth::user()->groupid;
            $creby   = auth::user()->id;
            $fullname   = auth::user()->fullname;
            $contentLogDel=$fullname.' đã xóa liên hệ của ('.$contact->fullname.')';
            DB::beginTransaction();
            try {
                actionLog::insert(
                    array(
                        'groupid'=>$groupid,
                         'created_by'=>$creby,
                         'title'=>'contact',
                         'content'=>$contact->channel.':'.$contentLogDel,
                         'detail'=>json_encode($contact)
                    )
                );
                customerContactRelation::where('contact_id',$contact->id)->where('groupid',$groupid)->delete();
                $contact->delete();
                DB::commit();
            } catch (\Exception $ex) {
                DB::rollback();
                return MyHelper::response(false,$ex->getMessage(), [],500);
            }
        }
        return MyHelper::response(true,'Delete Contact Successfully', [],200);
    }

}

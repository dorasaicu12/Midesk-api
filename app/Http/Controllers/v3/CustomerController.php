<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use App\Http\Functions\CheckField;
use App\Http\Functions\MyHelper;
use App\Http\Requests\CustomerRequest;
use App\Libraries\Encryption;
use App\Models\agentCustomerRelation;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\customerContactRelation;
use App\Models\CustomerRelationModel;
use App\Models\GroupTable;
use App\Models\Tags;
use App\Models\Ticket;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @group  Customer Management
 *
 * APIs for managing customer
 */

class CustomerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v3/customer",
     *     tags={"Customer"},
     *     summary="Get list customer",
     *     description="<h2>This API will Get list customer with condition below</h2>",
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
     *         example="fullname<=>nguyễn văn a",
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
     *         example="id,fullname,phone",
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
     *                   @OA\Property(property="fullname",type="string", example="văn A"),
     *                   @OA\Property(property="phone",type="string", example="0362548726"),
     *                   @OA\Property(property="phone_other",type="string", example="0987654321"),
     *                   @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
     *                   @OA\Property(property="address",type="string", example="abc/271/10"),
     *                   @OA\Property(property="province",type="string", example="HCM"),
     *                 ),
     *                 @OA\Property(property="current_page",type="string", example="1"),
     *                 @OA\Property(property="first_page_url",type="string", example="null"),
     *                 @OA\Property(property="next_page_url",type="string", example="null"),
     *                 @OA\Property(property="last_page_url",type="string", example="null"),
     *                 @OA\Property(property="prev_page_url",type="string", example="null"),
     *                 @OA\Property(property="from",type="string", example="1"),
     *                 @OA\Property(property="to",type="string", example="1"),
     *                 @OA\Property(property="total",type="string", example="1"),
     *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/customer"),
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
            $checkFileds = CheckField::check_fields($req, 'customer');
            if ($checkFileds) {
                Log::channel('customer_history')->info('Retrive Customer Data Failed by error:' . $checkFileds . '', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds = CheckField::check_order($req, 'customer');
            if ($checkFileds) {
                Log::channel('customer_history')->info('Retrive Customer Data Failed by error:' . $checkFileds . '', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds = CheckField::CheckSearch($req, 'customer');
            if ($checkFileds) {
                Log::channel('customer_history')->info('Retrive Customer Data Failed by error:' . $checkFileds . '', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
            $checksearch = CheckField::check_exist_of_value($req, 'customer');

            if ($checksearch) {
                Log::channel('customer_history')->info('Retrive Customer Data Failed by error:' . $checksearch . '', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
                return MyHelper::response(false, $checksearch, [], 404);
            }
        }

        $customers = (new Customer)->getListDefault($req);
        Log::channel('customer_history')->info('Retrive Customer Data Successfully', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
        return MyHelper::response(true, 'Successfully', $customers, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/customer/{customerId}",
     *     tags={"Customer"},
     *     summary="Find customer by customerId",
     *     description="<h2>This API will find customer by {customerId} and return only a single record</h2>",
     *     operationId="show",
     *     @OA\Parameter(
     *         name="customerId",
     *         in="path",
     *         description="<h4>This is the id of the customer you are looking for</h4>
    <code>Type: <b id='require'>Number</b></code>",
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
     *             @OA\Property(property="fullname",type="string", example="Nguyễn văn A"),
     *             @OA\Property(property="phone",type="string", example="0987654321"),
     *             @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
     *             @OA\Property(property="address",type="string", example="123/12b"),
     *             @OA\Property(property="province",type="string", example="Hồ Chí Minh"),
     *             @OA\Property(property="country",type="string", example="Việt Nam"),
     *           ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Will be return customer not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="string", example="Customer not found"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function show($id, Request $request)
    {
        $customer = (new Customer)->showOne($id);
        $id_tags = explode(',', $customer['tag']);
        $tags = Tags::whereIn('id', $id_tags)->get();
        $group = GroupTable::where('id', $customer['group_id'])->select(['id', 'group_name', 'group_type', 'description'])->first();
        $relation = CustomerRelationModel::where('id', $customer['relation_id'])->select(['id', 'title', 'description'])->first();
        $owner = agentCustomerRelation::where('customer_id', $customer['id'])->first();
        $contactId = customerContactRelation::where('customer_id', $id)->first();
        if ($contactId) {
            $encryption = new Encryption;
            $data = Contact::where('id', $contactId->contact_id)->first();
            if ($data->avatar == null) {
                $path = 'https://dev2022.midesk.vn/upload/images/userthumb/' . 'no_user_photo-v1.jpg';
            } else {
                $path = 'https://dev2022.midesk.vn/upload/images/userthumb/' . $data->avatar;
            }
            $user = ['avatar' => $path];
        } else {
            $user = null;
        }
        $customer['get_all_tags'] = $tags;
        $customer['get_group'] = $group;
        $customer['get_relation'] = $relation;
        $customer['get_owner'] = $owner;
        $customer['get_user'] = $user;
        if (!$customer) {
            Log::channel('customer_history')->info('Customer not found', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
            return MyHelper::response(false, 'Customer not found', [], 404);
        }
        Log::channel('customer_history')->info('Successfully', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
        return MyHelper::response(true, 'Successfully', $customer, 200);
    }

    /**
     * @OA\POST(
     *     path="/api/v3/customer",
     *     tags={"Customer"},
     *     summary="Create a customer",
     *     description="<h2>This API will Create a customer with json form below</h2><br><code>Press try it out button to modified</code>",
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
    <td>This is full name of customer</td>
    <td>true</td>
    </tr>
    <tr>
    <th>email</th>
    <td>This is email of customer</td>
    <td>True if without phone</td>
    </tr>
    <tr>
    <th>phone</th>
    <td>This is phone number of customer</td>
    <td>True if without email</td>
    </tr>
    <tr>
    <th>address</th>
    <td>This is address of customer</td>
    <td>false</td>
    </tr>
    <tr>
    <th>province</th>
    <td>This is province of customer</td>
    <td>false</td>
    </tr>
    </table><br><code>Click Schema to view data property</code>",
     *       @OA\JsonContent(
     *         required={"fullname","phone","email"},
     *         @OA\Property(property="fullname", type="string", example="Nguyễn văn A"),
     *         @OA\Property(property="email", type="string", example="acb@xyz"),
     *         @OA\Property(property="phone", type="string", example="0123456789"),
     *         @OA\Property(property="address", type="string", example="123/321"),
     *         @OA\Property(property="province", type="string", example="HCM"),
     *       ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Create Customer Successfully",
     *         @OA\JsonContent(
     *           @OA\Property(property="status", type="boolean", example="true"),
     *           @OA\Property(property="message", type="string", example="Create Customer Successfully"),
     *           @OA\Property(property="data",type="object",
     *             @OA\Property(property="id",type="string", example="1"),
     *           ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Create Customer failed",
     *         @OA\JsonContent(
     *           @OA\Property(property="status", type="boolean", example="true"),
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
    public function store(CustomerRequest $request)
    {

        if ($request->fullname == '') {
            Log::channel('customer_history')->info('fullname field is required', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
            return MyHelper::response(false, 'fullname field is required', [], 400);
        } else {
            $channel_list = ['facebook', 'zalo', 'webform', 'email', 'web', 'api'];
            $channel = 'api';
            if (array_key_exists('channel', $request)) {
                if (in_array($request->channel, $channel_list)) {
                    $channel = $request->channel;
                }
            }
            $customer['groupid'] = auth::user()->groupid;
            $customer['fullname'] = $request->fullname;
            $customer['phone'] = $request->phone ?? null;
            $customer['email'] = $request->email ?? null;
            $customer['address'] = $request->address ?? null;
            $customer['province'] = $request->province ?? null;
            $customer['createby'] = auth::user()->id;
            $customer['datecreate'] = time();
            $customer['channel'] = $channel;

            //     if($request->contact){
            //         foreach($request->contact as $key=>$val){
            //             $groupid = auth::user()->groupid;
            //             $creby   = auth::user()->id;

            //             $fullname = $val['fullname'];
            //             $phone    = $val['phone'] ?: "";
            //             $email    = $val['email'] ?: "";
            //             $address  = $val['address'] ?: "";
            //             $gender   = $val['gender'] ?: "";

            //             $channel_list  = ['facebook', 'zalo', 'webform', 'email', 'web', 'api'];
            //             $channel = 'api';
            //             if (array_key_exists('channel', $request->contact)) {
            //                 if (in_array($val['channel'], $channel_list)) {
            //                     $channel = $val['channel'];
            //                 }
            //             }
            //             $time     = time();
            //             $field = [];
            //             DB::beginTransaction();
            //             try {
            //                 //Kiểm tra tồn tại contact hay không

            //                 $check_contact = (new Contact)->checkContact($phone,$email);
            //                 DB::commit();

            //                 //Thêm mới Contact

            //                 if(!$check_contact){
            //                     $contact = new Contact();
            //                     $contact->address         = $address;
            //                     $contact->groupid       = $groupid;
            //                     $contact->fullname       = $fullname;
            //                     $contact->phone            = $phone;
            //                     $contact->email          = $email;
            //                     $contact->gender         = $gender;
            //                     $contact->channel        = $channel;
            //                     $contact->datecreate     = $time;
            //                     $contact->creby          = $creby;
            //                     if (!empty($val['custom_field'])) {
            //                         foreach ($val['custom_field'] as $key => $value) {
            //                             $key = str_replace('dynamic_', '', $key);
            //                             $check_field = CustomField::where('id',$key)->first();
            //                             if (!$check_field) {
            //                                 return MyHelper::response(true,'Custom Field '.$key.' Do not exists', null,200);
            //                             }else{
            //                                 $field[$key] = $value;
            //                             }
            //                         }
            //                         $custom_field = json_encode($field);
            //                         $contact->custom_fields = $custom_field;
            //                     }
            //                     $contact->save();
            //                     return (new customer)->createCustomer($customer);

            //                     $id_cus=customer::where('email',$customer['email'])->first();

            //                 }else{
            //                     return MyHelper::response(true,'Contact already exists', ['id' => $check_contact->id,'contact_id' => $check_contact->contact_id],200);
            //                 }

            //             } catch (\Exception $ex) {
            //                 DB::rollback();
            //                 return MyHelper::response(false,$ex->getMessage(), [],500);
            //             }
            //         }
            //    }else{
            //     return (new customer)->createCustomer($customer);
            //    }

            return (new customer)->createCustomer($customer, $request);

        }
    }

    /**
     * @OA\Put(
     *     path="/api/v3/customer/{$customerId}",
     *     tags={"Customer"},
     *     summary="Update customer by customerId",
     *     description="<h2>This API will update a customer by customerId and the value json form below</h2><br><code>Press try it out button to modified</code>",
     *     operationId="update",
     *     @OA\Parameter(
     *         name="customerId",
     *         in="path",
     *         example=1,
     *         description="<h4>This is the id of the customer you need update</h4>
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
    <td>This is full name of customer</td>
    <td>false</td>
    </tr>
    <tr>
    <th>email</th>
    <td>This is email of customer</td>
    <td>false</td>
    </tr>
    <tr>
    <th>phone</th>
    <td>This is phone num of customer</td>
    <td>false</td>
    </tr>
    <tr>
    <th>address</th>
    <td>This is address of customer</td>
    <td>false</td>
    </tr>
    <tr>
    <th>province</th>
    <td>This is province of customer</td>
    <td>false</td>
    </tr>
    </table><br><code>Click Schema to view data property</code>",
     *       @OA\JsonContent(
     *         required={"fullname","phone","email"},
     *         @OA\Property(property="fullname",type="string", example="Ngô văn B"),
     *         @OA\Property(property="phone",type="string", example="0987654321"),
     *         @OA\Property(property="email",type="string", example="abc@xyz123"),
     *         @OA\Property(property="address", type="string", example="123/321/HCM"),
     *         @OA\Property(property="province", type="string", example="male"),
     *       ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Update successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="true"),
     *              @OA\Property(property="message", type="string", example="Update customer successfully"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Will be return customer not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="string", example="Customer not found"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     },
     * )
     */
    public function update(CustomerRequest $request, $id)
    {
        $check_customer = (new Customer)->showOne($id);
        if (!$check_customer) {
            Log::channel('customer_history')->info('Customer Not found', ['request' => $request]);
            return MyHelper::response(true, 'Customer Not found', [], 404);
        } else {
            $request = array_filter($request->all());
            $request['dateupdate'] = time();
            $request['createby_update'] = auth::user()->id;
            // echo json_decode($request);
            // exit;
            $check_customer->update($request);
            if (!$check_customer) {
                Log::channel('customer_history')->info('Updated Customer Failed', ['request' => $request]);
                return MyHelper::response(false, 'Updated Customer Failed', [], 500);
            }
            Log::channel('customer_history')->info('Updated Customer successfully', ['request' => $request]);
            return MyHelper::response(true, 'Updated Customer successfully', [], 200);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v3/customer/{customerId}",
     *     tags={"Customer"},
     *     summary="Delete a customer by customerId",
     *     description="<h2>This API will delete a customer by customerId</h2>",
     *     operationId="destroy",
     *     @OA\Parameter(
     *         name="customerId",
     *         in="path",
     *         example=1,
     *         description="<h4>This is the id of the customer you need delete</h4>
    <code>Type: <b id='require'>Number</b></code>",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delete customer successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="true"),
     *              @OA\Property(property="message", type="string", example="Delete customer successfully"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="string", example="Customer not found"),
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
        $customer = (new Customer)->showOne($id);
        if (!$customer) {
            Log::channel('customer_history')->info('Customer Not Found', ['id' => $id]);
            return MyHelper::response(false, 'Customer Not Found', [], 404);
        } else {
            $customer->delete();
        }
        Log::channel('customer_history')->info('Delete Customer Successfully', ['id' => $id]);
        return MyHelper::response(true, 'Delete Customer Successfully', [], 200);
    }

    public function customerTicket(Request $request, $id)
    {
        $req = $request->all();
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $checkFileds = CheckField::check_fields($req, 'ticket');
            if ($checkFileds) {
                Log::channel('customer_history')->info('Customer Data retrive failed by error :' . $checkFileds . '', ['request' => $req]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds = CheckField::check_order($req, 'ticket');
            if ($checkFileds) {
                Log::channel('customer_history')->info('Customer Data retrive failed by error :' . $checkFileds . '', ['request' => $req]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds = CheckField::CheckSearch($req, 'ticket_2');
            if ($checkFileds) {
                Log::channel('customer_history')->info('Customer Data retrive failed by error :' . $checkFileds . '', ['request' => $req]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
            $checksearch = CheckField::check_exist_of_value($req, 'ticket_' . auth::user()->groupid . '');

            if ($checksearch) {
                Log::channel('customer_history')->info('Customer Data retrive failed by error :' . $checksearch . '', ['request' => $req]);
                return MyHelper::response(false, $checksearch, [], 404);
            }
        }
        $tickets = (new Ticket)->getDefaultByCustomerId($req, $id);
        Log::channel('customer_history')->info('Customer Data retrive Successfully', ['request' => $req]);
        return MyHelper::response(true, 'Successfully', $tickets, 200);
    }

}
<?php

namespace App\Http\Controllers\Group_196;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Group_196\CustomerRequest;
use App\Http\Functions\MyHelper;
use App\Models\Customer;
use Carbon\Carbon;
use Auth;
use DB;
/**
 * @group  Customer Management
 *
 * APIs for managing customer
 */

class CustomerController extends Controller
{
    /**
     * Get All Customer
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
        $customers = new Customer;
        if (!empty($req['k']) && !in_array($req['k'], $customers->getFillable())) {
            return MyHelper::response(false,'Key Search Not Found',[],404);
        }
        $customers = $customers->getDefault($req);
        return MyHelper::response(true,'Successfully',$customers,200);
    }

    /**
     * Show A Customer.
     *
     * @queryParam customer int required id of the customer.
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Successfully",
     *   "data": {
            "id": 2,
            "customer_id": "MKH000002",
            "honor": "company",
            "firstname": null,
            "lastname": null,
            "fullname": "KH 13",
            "phone": "0362694452",
            "phone_other": "0362548721,",
            "email": "dttien1@gmail.com",
            "address": "",
            "province_code": "VN-SG",
            "province": "Hồ Chí Minh",
            "district": "",
            "area": "Châu Á",
            "country": "Việt Nam",
            "createby": "13",
            "createby_update": "Mitek Admin"
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
        $customer = Customer::ShowOne($id);
        return MyHelper::response(true,'Successfully',$customer,200);
    }
    /**
     * Create A Customer
     *
     * @bodyParam fullname string required full name customer.
     * @bodyParam phone string required phone Example: 0123456789.
     * @bodyParam email string required email. Example: admin@gmail.com
     * @bodyParam address string optional address.
     * @bodyParam province string optional province.

     
     * @response 200 {
     *   "status": true,
     *   "message": "Create customer successfully",
     *   "data": {
     *       "id": "{$id}",
     *   }
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Resource not found"
     * }

     * @response  400 {
     *   "status": false,
     *   "message": "Fullname is require",
     *   "data": {}
     * }
     */
    public function store(CustomerRequest $request)
    {
        $channel_list  = ['facebook', 'zalo', 'webform', 'email', 'web', 'api'];
        $channel = 'api';
        if (array_key_exists('channel', $request)) {
            if (in_array($request->channel, $channel_list)) {
                $channel = $request->channel;
            }
        }
        $customer['groupid']  = auth::user()->groupid;
        $customer['fullname'] = $request->fullname;
        $customer['phone']   = $request->phone ?? null;
        $customer['email']   = $request->email ?? null;
        $customer['address']   = $request->address ?? null;
        $customer['province']   = $request->province ?? null;
        $customer['createby']   = auth::user()->id;
        $customer['datecreate']   = time();
        $customer['channel']   = $channel;

        DB::beginTransaction();
        try {
            $ctm = Customer::create($customer);

        DB::commit();
            return MyHelper::response(true,'Create customer successfully', ['id' => $ctm->id],200);
        } catch (\Exception $ex) {

        DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],500);
        }
    }
    /**
     * Update Customer
     * @bodyParam fullname string required full name customer.
     * @bodyParam phone string required phone Example: 0123456789.
     * @bodyParam email string required email. Example: admin@gmail.com
     * @bodyParam address string optional address.
     * @bodyParam province string optional province.

     * @response 200 {
     *   "status": true,
     *   "message": "Update customer successfully",
     *   "data": []
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Resource Not Found"
     * }
     
     * @response  400 {
     *   "status": false,
     *   "message": "fullname is require",
     *   "data": {}
     * }
     */
    public function update(CustomerRequest $request,$id)
    {
    	$check_customer = Customer::ShowOne($id);
    	if(!$check_customer){
            return MyHelper::response(true,'Customer Not found', [],400);
        }else{
            $check_customer->fullname   	= $request->fullname;
            $check_customer->phone    		= $request->phone;
            $check_customer->email      	= $request->email;                  
            $check_customer->province     	= $request->province;            
            $check_customer->address     	= $request->address;        
            $check_customer->dateupdate 	= time();
            $check_customer->save();
            if(!$check_customer){
                return MyHelper::response(false,'Updated Customer Failed', [],500);
            }
            return MyHelper::response(true,'Updated Customer successfully', [],200);
        }  
    }

}

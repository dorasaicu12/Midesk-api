<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Http\Functions\MyHelper;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderGroup;
use App\Models\OrderStatus;
use Carbon\Carbon;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Ticket;
use App\Traits\ProcessTraits;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use Auth;
use DB;
/**
 * @group  Order Management
 *
 * APIs for managing order
 */

class OrderController extends Controller
{
    use ProcessTraits;

    /**
    * @OA\Get(
    *     path="/api/v3/order",
    *     tags={"Order"},
    *     summary="Get list order",
    *     description="<h2>This API will Get list order with condition below</h2>",
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
    *         example="title<=>Gọi nhỡ",
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
    *         example="title,status,content",
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
    *                   @OA\Property(property="ord_code",type="string", example="null"),
    *                   @OA\Property(property="ord_address",type="string", example="null"),
    *                   @OA\Property(property="ord_description",type="string", example="null"),
    *                   @OA\Property(property="ord_group_name",type="string", example="null"),
    *                   @OA\Property(property="ord_group",type="string", example="null"),
    *                   @OA\Property(property="ord_status_value",type="string", example="null"),
    *                   @OA\Property(property="ord_status",type="string", example="null"),
    *                   @OA\Property(property="ord_discount",type="string", example="null"),
    *                   @OA\Property(property="ord_surcharge",type="string", example="null"),
    *                   @OA\Property(property="ord_total",type="string", example="null"),
    *                   @OA\Property(property="ord_rest_of_total",type="string", example="null"),
    *                   @OA\Property(property="ord_ship",type="string", example="null"),
    *                   @OA\Property(property="ord_customer_id",type="string", example="null"),
    *                   @OA\Property(property="ord_customer_name",type="string", example="null"),
    *                 ),
    *                 @OA\Property(property="current_page",type="string", example="1"),
    *                 @OA\Property(property="first_page_url",type="string", example="null"),
    *                 @OA\Property(property="next_page_url",type="string", example="null"),
    *                 @OA\Property(property="last_page_url",type="string", example="null"),
    *                 @OA\Property(property="prev_page_url",type="string", example="null"),
    *                 @OA\Property(property="from",type="string", example="1"),
    *                 @OA\Property(property="to",type="string", example="1"),
    *                 @OA\Property(property="total",type="string", example="1"),
    *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/order"),
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
        $orders = new Order;
        $orders->setDeleteColumn('flag_delete');
        $orders = $orders->getListDefault($req);
        return MyHelper::response(true,'Successfully',$orders,200);
    }

    /**
    * @OA\Get(
    *     path="/api/v3/order/{orderCode}",
    *     tags={"Order"},
    *     summary="Find order by orderCode",
    *     description="<h2>This API will find order by {orderCode} and return only a single record</h2>",
    *     operationId="show",
    *     @OA\Parameter(
    *         name="orderCode",
    *         in="path",
    *         description="<h4>This is the order code of the order you are looking for</h4>
              <code>Type: <b id='require'>String</b></code>",
    *         example="DH0001",
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
    *             @OA\Property(property="ord_code",type="string", example="DH000002"),
    *             @OA\Property(property="ord_address",type="string", example="null"),
    *             @OA\Property(property="ord_description",type="string", example="This is a description"),
    *             @OA\Property(property="ord_group_name",type="string", example="Test"),
    *             @OA\Property(property="ord_status_value",type="string", example="null"),
    *             @OA\Property(property="ord_status",type="string", example="1"),
    *             @OA\Property(property="ord_discount",type="string", example="0"),
    *             @OA\Property(property="ord_surcharge",type="string", example="1"),
    *             @OA\Property(property="ord_total",type="string", example="100"),
    *             @OA\Property(property="ord_rest_of_total",type="string", example="101"),
    *             @OA\Property(property="ord_ship",type="string", example="20"),
    *             @OA\Property(property="ord_customer_name",type="string", example="BMW VN"),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Order not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Order not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     }
    * )
    */

    public function show($ordid)
    {
        $order = (new Order)->checkExist($ordid);
        if (!$order) {
            return MyHelper::response(false,'Order not found',[],404);
        }
        return MyHelper::response(true,'Successfully',$order,200);
    }

    /**
    * @OA\POST(
    *     path="/api/v3/order",
    *     tags={"Order"},
    *     summary="Create a order",
    *     description="<h2>This API will Create a order with json form below</h2><br><code>Press try it out button to modified</code>",
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
                    <th>order_status</th>
                    <td>This is status of order</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>order_id</th>
                    <td>This is id of order</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>order_group</th>
                    <td>This is order channel</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>order_description</th>
                    <td>This is description of order</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>customer</th>
                    <td>
                        <table>
                            <tr><td colspan='3'> <br> <b id='require'>(id customer or create customer with array below)</b></td></tr>
                            <tr>
                                <th>fullname</th>
                                <td>This is name of customer</td>
                                <td>true</td>
                            </tr>
                            <tr>
                                <th>phone</th>
                                <td>This is phone of customer</td>
                                <td>true</td>
                            </tr>
                            <tr>
                                <th>email</th>
                                <td>This is email of customer</td>
                                <td>true</td>
                            </tr>
                            <tr>
                                <th>address</th>
                                <td>This is address of customer</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>locate</th>
                                <td>This is location of customer</td>
                                <td>false</td>
                            </tr>
                        </table>
                    </td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>contact</th>
                    <td>
                        <table>
                            <tr><td colspan='3'> <br> <b id='require'>(id contact or create contact with array below)</b></td></tr>
                            <tr>
                                <th>fullname</th>
                                <td>This is contact name of contact</td>
                                <td>true</td>
                            </tr>
                            <tr>
                                <th>phone</th>
                                <td>This is contact phone of contact</td>
                                <td>true</td>
                            </tr>
                            <tr>
                                <th>email</th>
                                <td>This is contact email of contact</td>
                                <td>true</td>
                            </tr>
                            <tr>
                                <th>address</th>
                                <td>This is contact address of contact</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>locate</th>
                                <td>This is contact location of contact</td>
                                <td>false</td>
                            </tr>
                        </table>
                    </td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>products</th>
                    <td>
                        <table>
                            <tr>
                                <th>image_product</th>
                                <td>Image of product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>code_product</th>
                                <td>code of product</td>
                                <td>true</td>
                            </tr>
                            <tr>
                                <th>name_product</th>
                                <td>Product name</td>
                                <td>true</td>
                            </tr>
                            <tr>
                                <th>cost_product</th>
                                <td>cost of product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>price_product</th>
                                <td>Price product for sell</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>color_product</th>
                                <td>Product color</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>option_product</th>
                                <td>Option of product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>gift_product</th>
                                <td>gift when buy product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>notes_product</th>
                                <td>notes for product</td>
                                <td>false</td>
                            </tr>
                        </table>
                    </td>
                    <td>true</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       @OA\JsonContent(required={"customer.fullname,contact.fullname,product.code_product,product.name_product"},
    *         @OA\Property(property="order_status", type="string", example="1"),
    *         @OA\Property(property="order_id", type="string", example="DH001"),
    *         @OA\Property(property="order_group", type="string", example="1"),
    *         @OA\Property(property="order_description", type="string", example="notes to order"),
    *         @OA\Property(property="products", type="array", 
    *           @OA\Items(type="object",required={"code_product","name_product"},
    *             @OA\Property(property="image_product",type="string", example="https://dbk.vn/uploads/ckfinder/images/1-content/anh-dep-1.jpg"),
    *             @OA\Property(property="code_product",type="string", example="H001"),
    *             @OA\Property(property="name_product",type="string", example="Iphone 3 promax"),
    *             @OA\Property(property="cost_product",type="string", example="2,000,000"),
    *             @OA\Property(property="price_product",type="string", example="20,000,000"),
    *             @OA\Property(property="color_product",type="string", example="Xanh"),
    *             @OA\Property(property="option_product",type="string", example="1 mắt"),
    *             @OA\Property(property="gift_product",type="string", example="ốp lưng"),
    *             @OA\Property(property="notes_product",type="string", example="nothing to notes"),
    *			),
    *		  ),
    *         @OA\Property(property="customer", type="object", 
    *             @OA\Property(property="fullname",type="string", example="Nguyễn văn A"),
    *             @OA\Property(property="email",type="string", example="acbxyx@gmail.com"),
    *             @OA\Property(property="phone",type="string", example="0123456789"),
    *             @OA\Property(property="address",type="string", example="86/3/Bình an/Quận 2"),
    *             @OA\Property(property="locate",type="string", example="HCM"),
    *         ),
    *         @OA\Property(property="contact", type="object", 
    *             @OA\Property(property="fullname",type="string", example="Nguyễn văn A"),
    *             @OA\Property(property="email",type="string", example="acbxyx@gmail.com"),
    *             @OA\Property(property="phone",type="string", example="0123456789"),
    *             @OA\Property(property="address",type="string", example="86/3/Bình an/Quận 2"),
    *             @OA\Property(property="locate",type="string", example="HCM"),
    *         ),
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Create order Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="true"),
    *           @OA\Property(property="message", type="string", example="Create order Successfully"),
    *              @OA\Property(property="data",type="object",
    *                @OA\Property(property="ord_code",type="string", example="1"),
    *              ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=422,
    *         description="Create Order failed",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="false"),
    *           @OA\Property(property="message", type="string", example="The given data was invalid."),
    *           @OA\Property(property="errors",type="object",
    *             @OA\Property(property="contact.fullname",type="array", 
    *               @OA\Items(type="string", example="The contact.fullname field is required.")
    *             ),
    *           )
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function store(OrderRequest $request)
    {
        $groupid = auth::user()->groupid;
        // $image_product = $request->image_product;
        // define contact id
        $id_contact = Contact::checkContact($request->customer_phone,$request->customer_email);
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
        //check customer
        $customer_list=[$request->customer];
        foreach($customer_list as $key2 =>$cus ){
            $checkcustomer=Customer::where('phone', $cus['phone'] )->where('email',$cus['email'])->first();
            if(!$checkcustomer){
                //tao customer khi ko tim ra customer
                $channel_list  = ['facebook', 'zalo', 'webform', 'email', 'web', 'api'];
                $channel = 'api';
                if (array_key_exists('channel', $request)) {
                    if (in_array($request->channel, $channel_list)) {
                        $channel = $request->channel;
                    }
                }
                $customer['groupid']  = auth::user()->groupid;
                $customer['fullname'] = $cus['fullname'];
                $customer['phone']   = $cus['phone'] ?? null;
                $customer['email']   = $cus['email'] ?? null;
                $customer['address']   = $cus['address'] ?? null;
                $customer['province']   = $cus['province'] ?? null;
                $customer['createby']   = auth::user()->id;
                $customer['datecreate']   = time();
                $customer['channel']   = $channel;
        
                DB::beginTransaction();
                try {
                    $response = Customer::create($customer);
        
                DB::commit();
                $customer = Customer::ShowOne($response->id);
                    //tao customer thanh cong

                } catch (\Exception $ex) {
        
                DB::rollback();
                    return MyHelper::response(false,$ex->getMessage(), [],500);
                }
                $id_customer = $customer->id;
            }else{
                $id_customer = $checkcustomer->id;
            }
        }


        $product_list = $request->products;
        $message = [];
        $product_list = $request->products;
        $ord_status = $request->order_status;
        $ord_id = $request->order_id;
        $ord_group = $request->order_group;
        $ord_description = $request->order_description;
        $ord_address = $request->order_address;
        $ord_total = 0;
        $id_ticket = $request->ticket_id;
        $order_detail = [];
        // check order status
        $id_ord_status = OrderStatus::where([['groupid',$groupid],['id',$ord_status]])->first();
        if (!$id_ord_status) {
            return MyHelper::response(false,'Order status do not match!',[],403);
        }
        // check order channel
        $id_ord_group = OrderGroup::where([['groupid',$groupid],['id',$ord_group]])->first();
        if (!$id_ord_group) {
            return MyHelper::response(false,'Order Group do not match!',[],403);
        }
        // check ticket id 
        if ($id_ticket) {
            if ($ticket = Ticket::find($id_ticket)) {
                $code_ticket = $ticket->ticket_id; 
            }else{
                $id_ticket = null;
            }
        }
        DB::beginTransaction();

        try {
            DB::commit();
            // check contact
            if (is_array($request->contact)) {
                $contact_phone = $request->contact['phone'];
                $contact_email = $request->contact['email'];
                $contact_name = $request->contact['fullname'];
                $contact_address = $request->contact['address'];
                $contact_locate = $request->contact['locate'];
                $contact = new Contact;
                $check_contact = $contact->checkContact($contact_phone,$contact_email);
                if(!$check_contact){
                    $contact->groupid       = $groupid;
                    $contact->fullname      = $contact_name;
                    $contact->phone         = $contact_phone;
                    $contact->email         = $contact_email;
                    $contact->address       = $contact_address.'/'.$contact_locate;
                    $contact->save();
                    $id_contact = $contact->id;
                    $fullname_contact = $contact->fullname;
                }else{
                    $id_contact = $check_contact->id;
                    $fullname_contact = $check_contact->fullname;
                }
            }else{
                $contact = Contact::find($request->contact);
                if ($contact) {
                    $id_contact = $contact->id;
                    $fullname_contact = $contact->fullname;
                }
            }

            // check customer
            if (is_array($request->customer)) {
                $customer_phone = $request->customer['phone'];
                $customer_email = $request->customer['email'];
                $customer_name = $request->customer['fullname'];
                $customer_address = $request->customer['address'];
                $customer_locate = $request->customer['locate'];

                $customer = new Customer;
                $check_customer = $customer->checkCustomer($customer_phone,$customer_email);
                if(!$check_customer){
                    $customer->groupid       = $groupid;
                    $customer->fullname      = $customer_name;
                    $customer->phone         = $customer_phone;
                    $customer->email         = $customer_email;
                    $customer->address       = $customer_address.'/'.$customer_locate;
                    $customer->save();
                    $id_customer = $customer->id;
                    $fullname_customer = $customer->fullname;
                }else{
                    $id_customer = $check_customer->id;
                    $fullname_customer = $check_customer->fullname;
                }
            }else{
                $customer = Customer::find($request->customer);
                if ($customer) {
                    $id_customer = $customer->id;
                    $fullname_customer = $customer->fullname;
                }
            }


            $order = new Order;
            $order->ord_code = $ord_id ?? null;
            $order->groupid = $groupid;
            $order->ticket_id = $id_ticket ?? null;
            $order->ticket_code = $code_ticket ?? null;
            $order->ord_contact_id = $id_contact ?? null;
            $order->ord_contact_name = $fullname_contact ?? null;
            $order->ord_customer_id = $id_customer ?? null;
            $order->ord_customer_name = $fullname_customer ?? null;
            $order->ord_status = $ord_status;
            $order->ord_status_value = $id_ord_status->order_status_name;
            $order->ord_group = $ord_group;
            $order->ord_group_name = $id_ord_group->order_group_name;
            $order->ord_description = $ord_description;
            $order->ord_address = $ord_address ?? null;
            $order->created_by = auth::user()->id;
            $order->save();

            if (!array_key_exists(0, $product_list)) {
            	$product_list = [$product_list];
            }
            foreach ($product_list as $key => $prd) {
                if (!$prd['code_product'] || !$prd['name_product']) {
                    $message[] = 'Product '.($prd['code_product'] ? $prd['code_product'] : $prd['name_product']).' không tồn tại code, không thể tạo sản phẩm này';
                    break;
                }
                $data_product = [];
                // check product code
                $id_product = Product::checkProductByCode($prd['code_product'],$groupid);
                $prd_code = $prd['code_product'] ?? null;
                $prd_name = $prd['name_product'] ?? null;
                $prd_cost = $prd['cost_product'] ?? null;
                $prd_price = $prd['price_product'] ?? null;
                $prd_notes = $prd['notes_product'] ?? null;
                $prd_qty = $prd['qty_product'] ?? null;
                $prd_discount = $prd['discount_product'] ?? null;
                $prd_weight = $prd['weight_priduct'] ?? null;
                
                if (!$id_product) {
                    $product = new Product;
                    $product->groupid      = $groupid;
                    $product->channel      = 'web';
                    $product->product_code = $prd_code;
                    $product->product_name = $prd_name;
                    $product->product_full_name = $prd_name;
                    $product->product_orig_price = $prd_cost;
                    $product->product_price = $prd_price;
                    $product->product_description = $prd_notes;
                    $product->created_by = 'api';
                    $product->save();
                    $id_product = $product->id;
                }else{
                    $id_product = $id_product->id;
                }

                $subtotal = '';
                if ($prd_qty != '' && $prd_price != '') {
                    $subtotal = (int) preg_replace('/[^A-Za-z0-9\-]/', '',$prd_price) * (int) $prd_qty;
                    $ord_total += $subtotal;
                }
                $order_detail[] = [
                    'groupid'        => $groupid,
                    'order_id'       => $order->id,
                    'product_id'     => $id_product,
                    'channel'        => 'api',
                    'quantity'       => $prd_qty,
                    'weight'         => $prd_weight,
                    'price'          => $prd_price,
                    'discount'       => $prd_discount,
                    'product_desc'   => $prd_notes,
                    'product_name'   => $prd_name,
                    'product_code'   => $prd_code,
                    'sub_total'       => $subtotal,
                ];
            }
            OrderDetail::insert($order_detail);
            $order->ord_rest_of_total = $ord_total;
            $order->save();
            $ordCode = $order->find($order->id)->ord_code;
            return MyHelper::response(true,(empty($message) ? 'Created Order successfully' : $message ),['order_code' => $ordCode],200);
        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],403);
        }
        
        return MyHelper::response(true,(empty($message) ? 'Created Order successfully' : $message ),[$id_customer],200);
    }


    /**
    * @OA\Put(
    *     path="/api/v3/order/{$orderCode}",
    *     tags={"Order"},
    *     summary="Update order",
    *     description="<h2>This API will update a order by orderCode and the value json form below</h2><br><code>Press try it out button to modified</code>",
    *     operationId="update",
    *     @OA\Parameter(
    *         name="orderCode",
    *         in="path",
    *         description="<h4>This is the code of the order you need update</h4>
              <code>Type: <b id='require'>String</b></code>",
    *         required=true,
    *         @OA\Schema(
    *             type="string",
    *         ),
    *     ),
    *     @OA\RequestBody(
    *       required=true,
    *       description="<table id='my-custom-table'>
                <tr>
                    <th>Name</th>
                    <td>Description</td>
                    <td><b id='require'>Required</b></td>
                </tr>
                <tr>
                    <th>order_status</th>
                    <td>This is status of order</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>order_id</th>
                    <td>This is id of order</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>order_group</th>
                    <td>This is order channel</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>order_description</th>
                    <td>This is description of order</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>products</th>
                    <td>
                        <table>
                            <tr>
                                <th>image_product</th>
                                <td>Image of product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>code_product</th>
                                <td>code of product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>name_product</th>
                                <td>Product name</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>cost_product</th>
                                <td>cost of product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>price_product</th>
                                <td>Price product for sell</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>color_product</th>
                                <td>Product color</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>option_product</th>
                                <td>Option of product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>gift_product</th>
                                <td>gift when buy product</td>
                                <td>false</td>
                            </tr>
                            <tr>
                                <th>notes_product</th>
                                <td>notes for product</td>
                                <td>false</td>
                            </tr>
                        </table>
                    </td>
                    <td>false</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       @OA\JsonContent(
    *         @OA\Property(property="order_status", type="string", example="1"),
    *         @OA\Property(property="order_id", type="string", example="DH001"),
    *         @OA\Property(property="order_group", type="string", example="1"),
    *         @OA\Property(property="order_description", type="string", example="notes to order"),
    *         @OA\Property(property="products", type="array", 
    *           @OA\Items(type="object",required={"code_product","name_product"},
    *             @OA\Property(property="image_product",type="string", example="https://dbk.vn/uploads/ckfinder/images/1-content/anh-dep-1.jpg"),
    *             @OA\Property(property="code_product",type="string", example="H001"),
    *             @OA\Property(property="name_product",type="string", example="Iphone 3 promax"),
    *             @OA\Property(property="cost_product",type="string", example="2,000,000"),
    *             @OA\Property(property="price_product",type="string", example="20,000,000"),
    *             @OA\Property(property="color_product",type="string", example="Xanh"),
    *             @OA\Property(property="option_product",type="string", example="1 mắt"),
    *             @OA\Property(property="gift_product",type="string", example="ốp lưng"),
    *             @OA\Property(property="notes_product",type="string", example="nothing to notes"),
    *           ),
    *         ),
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Update successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Update order successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Order not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Order not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function update(Request $request,$ordid)
    {
        $groupid = auth::user()->groupid;
        $message = [];
        $product_list = $request->products;
        $ord_id = $request->order_id;
        $ord_description = $request->order_description;
        $ord_address = $request->order_address;
        $ord_total = 0;
        $id_ticket = $request->ticket_id;
        $order_detail = [];
        // check order status
        if (!$ordid) {
            return MyHelper::response(false,'OrderId not found!',[],404);
        }
        $order = Order::where([['ord_code',$ordid],['groupid',$groupid]])->first();
        if (!$order) {
            return MyHelper::response(false,'Order not found!',[],404);
        }
        if ($request->order_status) {
            $ord_status = $request->order_status;
            $id_ord_status = OrderStatus::where([['groupid',$groupid],['id',$ord_status]])->first();
            if (!$id_ord_status) {
                return MyHelper::response(false,'Order status do not match!',[],403);
            }            
        }
        // check order channel
        if ($request->order_group) {
            $ord_group = $request->order_group;
            $id_ord_group = OrderGroup::where([['groupid',$groupid],['id',$ord_group]])->first();
            if (!$id_ord_group) {
                return MyHelper::response(false,'Order Group do not match!',[],403);
            }
        }

        // check ticket id 
        if ($id_ticket) {
            if ($ticket = Ticket::find($id_ticket)) {
                $code_ticket = $ticket->ticket_id; 
            }
        }
        DB::beginTransaction();

        try {
            DB::commit();
            $order->ord_code = $ord_id ?? $order->ord_code;
            $order->ticket_id = $id_ticket ?? $order->ticket_id;
            $order->ticket_code = $code_ticket ?? $order->ticket_code;
            $order->ord_status = $ord_status ?? $order->ord_status;
            $order->ord_status_value = $id_ord_status->order_status_name ?? $order->ord_status_value;
            $order->ord_group = $ord_group ?? $order->ord_group;
            $order->ord_group_name = $id_ord_group->order_group_name ?? $order->order_group_name;
            $order->ord_description = $ord_description ?? $order->ord_description;
            $order->ord_address = $ord_address ?? $order->ord_address;
            $order->save();
            if ($product_list) {
                if (!array_key_exists(0, $product_list)) {
                    $product_list = [$product_list];
                }

                OrderDetail::where('order_id',$order->id)->first()->Products()->delete();
                OrderDetail::where('order_id',$order->id)->delete();
                foreach ($product_list as $key => $prd) {
                    if (!$prd['code_product'] || !$prd['name_product']) {
                        $message[] = 'Product '.($prd['code_product'] ? $prd['code_product'] : $prd['name_product']).' không tồn tại code, không thể tạo sản phẩm này';
                        break;
                    }
                    $data_product = [];
                    // check product code
                    $id_product = Product::checkProductByCode($prd['code_product'],$groupid);
                    $prd_code = $prd['code_product'] ?? null;
                    $prd_name = $prd['name_product'] ?? null;
                    $prd_cost = $prd['cost_product'] ?? null;
                    $prd_price = $prd['price_product'] ?? null;
                    $prd_notes = $prd['notes_product'] ?? null;
                    $prd_qty = $prd['qty_product'] ?? null;
                    $prd_discount = $prd['discount_product'] ?? null;
                    $prd_weight = $prd['weight_priduct'] ?? null;
                    
                    if (!$id_product) {
                        $product = new Product;
                        $product->groupid      = $groupid;
                        $product->channel      = 'web';
                        $product->product_code = $prd_code;
                        $product->product_name = $prd_name;
                        $product->product_full_name = $prd_name;
                        $product->product_orig_price = $prd_cost;
                        $product->product_price = $prd_price;
                        $product->product_description = $prd_notes;
                        $product->created_by = 'api';
                        $product->save();
                        $id_product = $product->id;
                    }else{
                        $id_product = $id_product->id;
                    }

                    $subtotal = '';
                    if ($prd_qty != '' && $prd_price != '') {
                        $subtotal = (int) preg_replace('/[^A-Za-z0-9\-]/', '',$prd_price) * (int) $prd_qty;
                        $ord_total += $subtotal;
                    }
                    $order_detail[] = [
                        'groupid'        => $groupid,
                        'order_id'       => $order->id,
                        'product_id'     => $id_product,
                        'channel'        => 'api',
                        'quantity'       => $prd_qty,
                        'weight'         => $prd_weight,
                        'price'          => $prd_price,
                        'discount'       => $prd_discount,
                        'product_desc'   => $prd_notes,
                        'product_name'   => $prd_name,
                        'product_code'   => $prd_code,
                        'sub_total'       => $subtotal,
                    ];
                }
                OrderDetail::insert($order_detail);
                $order->ord_rest_of_total = $ord_total;
                $order->save();
            }
            
            return MyHelper::response(true,(empty($message) ? 'Updated Order successfully' : $message ),[],200);
        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false,$ex->getMessage(), [],403);
        }
    }

    /**
    * @OA\Delete(
    *     path="/api/v3/order/{orderCode}",
    *     tags={"Order"},
    *     summary="Delete a order by orderCode",
    *     description="<h2>This API will delete a order by orderCode</h2>",
    *     operationId="destroy",
    *     @OA\Parameter(
    *         name="orderCode",
    *         in="path",
    *         example=1,
    *         description="<h4>This is the id of the order you need delete</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         required=true,
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Delete order successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Delete order successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Order not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Order not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function destroy($orderid)
    {   
        $order = (new Order)->checkExist($orderid);
        if (!$order) {
            return MyHelper::response(false,'Order Not Found', [],404);
        }else{
            $order->delete();
        }
        return MyHelper::response(true,'Delete Order Successfully', [],200);
    }
}

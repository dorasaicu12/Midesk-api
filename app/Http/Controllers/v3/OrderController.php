<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\v3\OrderRequest;
use App\Http\Functions\MyHelper;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\OrderStatus;
use App\Models\ModelsTrait;
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
    use ModelsTrait;

    public function index(Request $request)
    {
        $req = $request->all();
        $orders = new Order;
        $orders = $orders->getDefault($req);
        return MyHelper::response(true,'Successfully',$orders,200);
    }

    public function show($id)
    {
        $customer = Order::ShowOne($id);
        if($customer){
            return MyHelper::response(true,'Successfully',$customer,200);
        }else{
            return MyHelper::response(false,'order not found',$customer,404);
        }
        
    }


    public function store(Request $request)
    {
        $groupid = auth::user()->groupid;
        // $image_product = $request->image_product;
        // define contact id
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
    }


    public function update(Request $request,$ordid)
    {
        $data = [];
        $groupid = auth::user()->groupid;
        $arr_status = OrderStatus::where('groupid',$groupid)->get()->pluck('order_status_name','id')->toArray();

        if (!$request->status) {
            return MyHelper::response(false,'Status is required',[],404);
        }

        $order = new Order;
        $check_order = $order->checkExist($ordid);   
        if (!$check_order) {
            Log::channel('orders_history')->info('Order not found',['status' => 404, 'id'=>$ordid,'request'=>$request->all()]);
            return MyHelper::response(false,'Resource Not Found',[],404);
        }else{
            if (!array_key_exists($request->status, $arr_status)) {
                return MyHelper::response(false,'Status incorrectly!',[],403);
            }
            $check_order->ord_status =  $request->status;
            $check_order->ord_status_value = $arr_status[$request->status];
            $check_order->save();
            return MyHelper::response(true,'Status order update successfully',[],200);
        }
    }
}

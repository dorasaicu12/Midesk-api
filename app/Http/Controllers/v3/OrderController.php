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
use App\Models\TicketsTrait;
use Illuminate\Support\Facades\Log;
use Auth;
/**
 * @group  Order Management
 *
 * APIs for managing order
 */

class OrderController extends Controller
{
    use TicketsTrait;
    /**
     * Create A Order
     *
     * @bodyParam customer_name string optional The customer name.
     * @bodyParam customer_phone string optional The customer phone.
     * @bodyParam customer_email string optional The customer email.
     * @bodyParam customer_address string optional The customer address.
     * @bodyParam customer_locate string optional The customer locate.
     * @bodyParam customer_note string optional The customer notes.
     * @bodyParam order_id string required id of order.
     * @bodyParam products array required The products array list .
     * @bodyParam products.image_product string optional Image of products.
     * @bodyParam products.code_product string required Product code.
     * @bodyParam products.name_product string required Product name.
     * @bodyParam products.cost_product string optional Product cost price.
     * @bodyParam products.price_product string optional Product price.
     * @bodyParam products.color_product string optional Product color.
     * @bodyParam products.option_product string optional Optional of product.
     * @bodyParam products.gift_product string optional gift of product.
     * @bodyParam products.notes_product string optional Product notes.
    
     
     * @response 200 {
     *   "status": true,
     *   "message": "Create order successfully",
     *   "data": {
     *       "id": "{$id}"
     *   }
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Order id do not exist"
     * }

     */
    public function store(Request $request)
    {
        $groupid = auth::user()->groupid;

        // $image_product = $request->image_product;
        // define contact id
        $check_contact = Contact::checkContact($request->customer_phone,$request->customer_email);
        if (!$check_contact) {
            $contact = new Contact;
            $contact->groupid       = $groupid;
            $contact->fullname      = $request->customer_name;
            $contact->phone         = $request->customer_phone;
            $contact->email         = $request->customer_email;
            $contact->address       = $request->customer_address.'/'.$request->customer_locate;
            $contact->save();
            $id_contact = $contact->id;
        }else{
            $id_contact = $check_contact->id;
        }
        $product_list = $request->products;
        
        $state = true;
        $message = [];
        foreach ($product_list as $key => $prd) {
            if (!$prd['code_product'] || !$prd['name_product']) {
                $message[] = 'Product '.($prd['code_product'] ? $prd['code_product'] : $prd['name_product']).' không tồn tại code, không thể tạo sản phẩm này';
                $state = false;
                break;
            }
            // check product code
            $check_product = Product::checkProductByCode($prd['code_product'],$groupid);
            if (!$check_product) {
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
                $message[] = 'Create Product successfully with #ID '.$id_product;
            }else{
                $id_product = $check_product->id;
                $message[] = 'Product already exist with #ID '.$id_product;
            }
        }
        if ($state) {
            return MyHelper::response(true,$message,[],200);
        }else{
            Log::channel('orders_history')->info('Created Order Failed',['status' => 500,['message'=>json_encode($message)] ,'request'=>$request->all()]);
            return MyHelper::response(false,$message, [],500);
        }
    }

    /**
     * Update status for order
     *
     * @urlParam order_id required The code of the order.
     * @urlParam product_id required The code of the product.
     * @urlParam status required The status change.

     
     * @response 200 {
     *   "status": true,
     *   "message": "Update order successfully",
     *   "data": []
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Status is required"
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Resource Not Found"
     * }

     */
    public function update(Request $request,$ordid)
    {
        $data = [];
        $arr_status = [
                        1 => 'Khởi tạo',
                        2 => 'Hủy bỏ',
                        3 => 'Hoàn thành',
                        51 => 'Bảo hành',
                        49 => 'Thông báo cước',
                        48 => 'Đơn hàng cũ',
                        39 => 'Follow-up',
                        45 => 'Under review',
                        46 => 'Demo',
                        47 => 'Negotiation',
                        52 => 'Thanh lý'
                    ];
        if (!$request->status) {
            return MyHelper::response(false,'Status is required',[],404);
        }

        $groupid = auth::user()->groupid;
        $order = new Order;
        $check_order = $order->checkExist($ordid);   
        if (!$check_order) {
            Log::channel('orders_history')->info('Order not found',['status' => 404, 'id'=>$ordid,'request'=>$request->all()]);
            return MyHelper::response(false,'Resource Not Found',[],404);
        }else{
            $check_order->ord_status =  $request->status;
            $check_order->ord_status_value = $arr_status[$request->status];
            $check_order->save();
            return MyHelper::response(true,'Status order update successfully',[],200);
        }
    }
}

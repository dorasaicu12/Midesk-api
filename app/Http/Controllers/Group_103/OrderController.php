<?php

namespace App\Http\Controllers\Group_103;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Group_103\OrderRequest;
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

        if ($groupid == '103') {
            if (!$request->order_id) {
                Log::channel('orders_history')->info('Order id not found',['status' => 200, 'request'=>$request->all()]);
                return MyHelper::response(false,'Order id not found', [],200);
            }
        }
        // $image_product = $request->image_product;
        // define contact id
        $id_contact = Contact::checkContactByPhone($request->customer_phone);
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
        $product_list = $request->products;
        
        $state = true;
        $message = ['Created Order successfully'];
        foreach ($product_list as $key => $prd) {
            if (!$prd['code_product'] || !$prd['name_product']) {
                $message[] = 'Product '.($prd['code_product'] ? $prd['code_product'] : $prd['name_product']).' không tồn tại code, không thể tạo sản phẩm này';
                break;
            }
            // check product code
            $id_product = Product::checkProductByCode($prd['code_product'],$groupid);
            if (!$id_product) {
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
            }else{
                $id_product = $id_product->id;
            }
            // check ticket exist
            if ($groupid == '103') {
                $ticket = new Ticket;
                $check_ticket = $ticket->checkExist_mtmb($request->order_id,$prd['code_product']);
                $ticket_id = '';
                if ($check_ticket) {
                    $ticket_id = $check_ticket->id;
                }
                if (array_key_exists('image_product', $prd) && $prd['image_product'] != '') {
                    $image = $prd['image_product'];
                }else{
                    $image = 'https://cskh.midesk.vn/public/images/no_image.png';
                }

                // create ticket
                $params = [];
                $params['contact_id'] = $id_contact;
                $params['title'] = "Tạo đơn hàng #".$request->order_id." với mã sản phẩm ".$prd['code_product'];
                $params['content'] = "
                                    <p>Kính gửi khách hàng : <b>".$request->customer_name."</b></p>
                                    <p>Điện thoại : <b>".$request->customer_phone."</b></p>
                                    <p>Email : <b>".$request->customer_email."</b></p>
                                    <p>Địa chỉ : <b>".$request->customer_address.'/'.$request->customer_locate."</b></p>
                                    <p>Ghi chú : <b>".$request->customer_note."</b></p>
                                    <h5>Thông tin đơn hàng <b>#".$request->order_id."</b></h5>
                                    <table class='table table-bordered table-striped table-hover' style='font-size:12px;'>
                                        <thead>
                                            <tr>
                                                <th class='text-center' >Ảnh</th>
                                                <th class='text-center' >Mã</th>
                                                <th class='text-center' >Sản phẩm</th>
                                                <th class='text-center' >Giá</th>
                                                <th class='text-center' >Giá Nhập</th>
                                                <th class='text-center' >Màu</th>
                                                <th class='text-center' >Tùy chọn</th>
                                                <th class='text-center' >Quà</th>
                                                <th class='text-center' >Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class='text-center'><img style='width:50px;' src=".$image." ></td>
                                                <td class='text-center' style='vertical-align: middle;'><span>".$prd['code_product']."</span></td>
                                                <td class='text-center' style='vertical-align: middle;'><span>".$prd['name_product']."</span></td>
                                                <td class='text-center' style='vertical-align: middle;'><span>".$prd['price_product']."</span></td>
                                                <td class='text-center' style='vertical-align: middle;'><span>".$prd['cost_product']."</span></td>
                                                <td class='text-center' style='vertical-align: middle;'><span>".$prd['color_product']."</span></td>
                                                <td class='text-center' style='vertical-align: middle;'><span>".$prd['option_product']."</span></td>
                                                <td class='text-center' style='vertical-align: middle;'><span>".$prd['gift_product']."</span></td>
                                                <td class='text-center' style='vertical-align: middle;'><span>".$prd['notes_product']."</span></td>
                                            </tr>
                                        </tbody>
                                    </table>";
                $params['channel'] = "api";
                $params['mt_orderid'] = $request->order_id;
                $params['mt_productid'] = $id_product;
                $params['mt_qty'] = ($key + 1).'/'.count($product_list);

                if (!$this->create_or_update_ticket($params,$ticket_id)) {
                    $state = false;
                }
                
            }else{

            }
            
        }
        if ($state) {
            return MyHelper::response(true,$message,[],200);
        }else{
            Log::channel('orders_history')->info('Created Order Failed',['status' => 500,['message'=>json_encode($message)] ,'request'=>$request->all()]);
            return MyHelper::response(false,'Created Order Failed', [],500);
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
        if ($groupid == '103') {
            $order = new Ticket;
            $check_order = $order->checkExist_mtmb($ordid,$request->product_id);
        }else{
            $order = new Order;
            $check_order = $order->checkExist($ordid);   
        }
        if (!$check_order) {
            Log::channel('orders_history')->info('Order not found',['status' => 404, 'id'=>$ordid,'request'=>$request->all()]);
            return MyHelper::response(false,'Resource Not Found',[],404);
        }else{
            if ($groupid == '103') {
                $check_order->mt_status = $request->status;
                $data['mt_status'] = $request->status;
                $this->create_comment($check_order->id,$data,'');
            }else{
                $check_order->ord_status =  $request->status;
                $check_order->ord_status_value = $arr_status[$request->status];
            }
            $check_order->save();
            return MyHelper::response(true,'Status order update successfully',[],200);
        }
    }
}

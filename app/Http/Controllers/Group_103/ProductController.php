<?php

namespace App\Http\Controllers\Group_103;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Group_103\ProductRequest;
use App\Http\Functions\MyHelper;
use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Auth;
use DB;
/**
 * @group  Product Management
 *
 * APIs for managing product
 */

class ProductController extends Controller
{
    /**
     * Create A Product
     *
     * @bodyParam branch string required The id of branch.
     * @bodyParam product_code string required product code Example: ABCXYZ.
     * @bodyParam product_is_active string required product state 1 = show , 0 = hide. Example: 1
     * @bodyParam product_name string required The name of product.
     * @bodyParam product_barcode string optional barcode.
     * @bodyParam product_stock string optional stock.
     * @bodyParam product_expire string optional day exprise.
     * @bodyParam category_id string required The id of category .
     * @bodyParam unit_id string required The id of unit product.
     * @bodyParam price string optional price of product.
     * @bodyParam cost_price string optional cost of product.
     * @bodyParam unlimited string optional sell not out of stock.
     * @bodyParam is_surcharge string optional insurcharge.
    
     
     * @response 200 {
     *   "status": true,
     *   "message": "Create product successfully",
     *   "data": {
     *       "id": "{$id}"
     *   }
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Branch do not exist"
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Product unit do not exist"
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "Product category do not exist"
     * }

     * @response  404 {
     *   "status": false,
     *   "message": "This product already exists"
     * }

     */
    public function store(ProductRequest $request)
    {
        $product = [];
        $groupid = auth::user()->groupid;
        // check branch_id
        $check_branch = Branch::where([ ['groupid',$groupid],['id',$request->branch] ])->first();
        if (!$check_branch) {
            return MyHelper::response(false,'Branch do not exist',[],404);
        }
        // check unit_id
        $check_unit = ProductUnit::where([ ['groupid',$groupid],['id',$request->unit_id] ])->first();
        if (!$check_unit) {
            return MyHelper::response(false,'Product unit do not exist',[],404);            
        }

        // check category_id
        $check_cate = ProductCategory::where([ ['groupid',$groupid],['id',$request->category_id] ])->first();
        if (!$check_cate) {
            return MyHelper::response(false,'Product category do not exist',[],404);            
        }

        // check product code
        $check_prod = Product::where([ ['groupid',$groupid],['product_code',$request->product_code] ])->first();
        if ($check_prod) {
            return MyHelper::response(false,'This product already exists',[],404);            
        }

        $product['branch_id'] = $request->branch;
        $product['product_unit_id'] = $request->unit_id;
        $product['product_category_id'] = $request->category_id;
        $product['product_code'] = $request->product_code;
        $product['product_is_active'] = $request->product_is_active;
        $product['product_name'] = $request->product_name;
        $product['product_full_name'] = $request->product_name;
        $product['product_barcode'] = $request->product_barcode;
        $product['product_stock'] = $request->product_stock;
        $product['product_day_expire'] = $request->product_expire;
        $product['product_price'] = $request->price;
        $product['product_orig_price'] = $request->cost_price;
        $product['product_unlimited'] = $request->unlimited;
        $product['is_surcharge'] = $request->is_surcharge;
        $product['created_by'] = 'api';
        $product['channel'] = 'web';
        $product['groupid']  = $groupid;

        DB::beginTransaction();
        try {
            $res = Product::create($product);
        DB::commit();
            return MyHelper::response(true,'Create Product successfully', ['id' => $res->id],200);
        } catch (\Exception $ex) {
        DB::rollback();
            Log::channel('product_history')->info('Create products failed',['status' => 403, 'message'=>$ex->getMessage()]);
            return MyHelper::response(false,$ex->getMessage(), [],500);
        }
    }

}

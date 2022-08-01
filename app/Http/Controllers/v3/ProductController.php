<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ProductRequest;
use App\Http\Functions\MyHelper;
use App\Http\Functions\CheckField;
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
    * @OA\Get(
    *     path="/api/v3/product",
    *     tags={"Product"},
    *     summary="Get list product",
    *     description="<h2>This API will Get list product with condition below</h2>",
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
    *         example="product_name<=>Iphone 13",
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
    *         example="id,product_code,product_name,product_orig_price",
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
    *                   @OA\Property(property="id",type="number", example="1"),
    *                   @OA\Property(property="product_code",type="string", example="null"),
    *                   @OA\Property(property="product_name",type="string", example="null"),
    *                   @OA\Property(property="product_orig_price",type="string", example="null"),
    *                   @OA\Property(property="product_price",type="string", example="null"),
    *                   @OA\Property(property="product_unit",type="string", example="null"),
    *                   @OA\Property(property="product_unit_id",type="number", example="null"),
    *                   @OA\Property(property="product_type",type="string", example="null"),
    *                   @OA\Property(property="product_weight",type="string", example="null"),
    *                   @OA\Property(property="product_stock",type="number", example="null"),
    *                   @OA\Property(property="product_unlimited",type="number", example="null"),
    *                   @OA\Property(property="product_allows_sale",type="string", example="null"),
    *                   @OA\Property(property="product_week_expire",type="number", example="null"),
    *                   @OA\Property(property="product_day_expire",type="number", example="null"),
    *                   @OA\Property(property="is_surcharge",type="number", example="null"),
    *                   @OA\Property(property="product_barcode",type="string", example="null"),
    *                   @OA\Property(property="product_origin",type="string", example="null"),
    *                   @OA\Property(property="created_at",type="string", example="2021-03-24 10:51:06"),
    *                 ),
    *                 @OA\Property(property="current_page",type="string", example="1"),
    *                 @OA\Property(property="first_page_url",type="string", example="null"),
    *                 @OA\Property(property="next_page_url",type="string", example="null"),
    *                 @OA\Property(property="last_page_url",type="string", example="null"),
    *                 @OA\Property(property="prev_page_url",type="string", example="null"),
    *                 @OA\Property(property="from",type="string", example="1"),
    *                 @OA\Property(property="to",type="string", example="1"),
    *                 @OA\Property(property="total",type="string", example="1"),
    *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/product"),
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
            $checkFileds= CheckField::check_fields($req,'product');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds= CheckField::check_order($req,'product');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds= CheckField::CheckSearch($req,'product');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
             $checksearch= CheckField::check_exist_of_value($req,'product');

             if($checksearch){
                return MyHelper::response(false,$checksearch,[],404);
             }
           } 
        $products = new Product;
        $products->setDeleteColumn('product_is_active');
        $products->setDeleteValue('1');
        $products = $products->getListDefault($req);
        return MyHelper::response(true,'Successfully',$products,200);
    }

    /**
    * @OA\Get(
    *     path="/api/v3/product/{productCode}",
    *     tags={"Product"},
    *     summary="Find product by productCode",
    *     description="<h2>This API will find product by {productCode} and return only a single record</h2>",
    *     operationId="show",
    *     @OA\Parameter(
    *         name="productCode",
    *         in="path",
    *         description="<h4>This is the code of the product you are looking for</h4>
              <code>Type: <b id='require'>String</b></code>",
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
    *             @OA\Property(property="product_code",type="string", example="DH00011"),
    *             @OA\Property(property="product_name",type="string", example="null"),
    *             @OA\Property(property="product_orig_price",type="string", example="null"),
    *             @OA\Property(property="product_price",type="string", example="null"),
    *             @OA\Property(property="product_unit",type="string", example="null"),
    *             @OA\Property(property="product_unit_id",type="string", example="null"),
    *             @OA\Property(property="product_type",type="string", example="null"),
    *             @OA\Property(property="product_weight",type="string", example="null"),
    *             @OA\Property(property="product_barcode",type="string", example="null"),
    *             @OA\Property(property="created_at",type="string", example="null"),
    *             @OA\Property(property="product_origin",type="string", example="null"),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Product not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Product not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     }
    * )
    */
    public function show($prod_code)
    {
        $product = new Product;
        $product->setDeleteColumn('product_is_active');
        $product->setSelectColumn('product_code');
        $product->setDeleteValue('1');
        $product = $product->showOne($prod_code);
        if (!$product) {
            return MyHelper::response(false,'Product not found',[],404);    
        }
        return MyHelper::response(true,'Successfully',$product,200);    
    }
    /**
    * @OA\POST(
    *     path="/api/v3/product",
    *     tags={"Product"},
    *     summary="Create a product",
    *     description="<h2>This API will Create a product with json form below</h2><br><code>Press try it out button to modified</code>",
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
                    <th>branch</th>
                    <td>The branch product</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>product_is_active</th>
                    <td>Product active (no = 0, yes = 1)</td>
                    <td>false (default = 1)</td>
                </tr>
                <tr>
                    <th>product_name</th>
                    <td>The name of product</td>
                    <td>true</td>
                </tr>
                <tr>
                    <th>product_barcode</th>
                    <td>Product barcode</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>product_stock</th>
                    <td>Product stock</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>product_expire</th>
                    <td>Date expire of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>category_id</th>
                    <td>Category of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>unit_id</th>
                    <td>Unit of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>price</th>
                    <td>The price of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>cost_price</th>
                    <td>The cost of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>unlimited</th>
                    <td>Allow to sell when this product is out of stock</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>is_surcharge</th>
                    <td>Is there any extra charge for the product? (no = 0,yes = 1)</td>
                    <td>false</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       @OA\JsonContent(
    *         required={"branch","product_code","product_name","product_is_active","category_id","unit_id"},
    *         @OA\Property(property="branch", type="string", example="1"),
    *         @OA\Property(property="product_is_active", type="string", example="1"),
    *         @OA\Property(property="product_name", type="string", example="IP 11 Promax"),
    *         @OA\Property(property="product_barcode", type="string", example="0123"),
    *         @OA\Property(property="product_stock", type="string", example="12"),
    *         @OA\Property(property="product_expire", type="string", example="2"),
    *         @OA\Property(property="category_id", type="string", example="132"),
    *         @OA\Property(property="unit_id", type="string", example="1"),
    *         @OA\Property(property="price", type="string", example="200,000,000"),
    *         @OA\Property(property="cost_price", type="string", example="100,000,000"),
    *         @OA\Property(property="unlimited", type="string", example="1"),
    *         @OA\Property(property="is_surcharge", type="string", example="1"),
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Create product Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="true"),
    *           @OA\Property(property="message", type="string", example="Create product Successfully"),
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="product_code",type="string", example="1"),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Create failed",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="false"),
    *           @OA\Property(property="message", type="string", example="This product already exists"),
    *           @OA\Property(property="data",type="string", example="[]"),
    *         )
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function store(ProductRequest $request)
    {
        $product = [];
        $groupid = auth::user()->groupid;

        // check branch_id
            $check_branch = Branch::where([ ['groupid',$groupid],['id',$request->branch] ])->first();


        if(isset($request->branch)){
            if (!$check_branch) {
                return MyHelper::response(false,'Branch do not exist',[],403);
            }
        }
        // check unit_id
        if (isset($request->unit_id)) {
            $check_unit = ProductUnit::where([ ['groupid',$groupid],['id',$request->unit_id] ])->first();
            if (!$check_unit) {
                return MyHelper::response(false,'Product unit do not exist',[],403);            
            }
        }

        // check category_id
        if (isset($request->category_id)) {
            $check_cate = ProductCategory::where([ ['groupid',$groupid],['id',$request->category_id] ])->first();
            
            if (!$check_cate) {
                return MyHelper::response(false,'Product category do not exist',[],403);
            }
        }

        // check product code
        $check_prod = Product::where([ ['groupid',$groupid],['product_code',$request->product_code] ])->first();
        if ($check_prod) {
            return MyHelper::response(false,'This product already exists',[],403);            
        }

        $product['branch_id'] = $request->branch ?? null;
        $product['product_unit_id'] = $request->unit_id ?? null;
        $product['product_category_id'] = $request->category_id ?? null;
        $product['product_code'] = $request->product_code;
        $product['product_is_active'] = $request->product_is_active ?? null;
        $product['product_name'] = $request->product_name;
        $product['product_full_name'] = $request->product_name;
        $product['product_barcode'] = $request->product_barcode ?? null;
        $product['product_stock'] = $request->product_stock ?? null;
        $product['product_day_expire'] = $request->product_expire ?? null;
        $product['product_price'] = $request->price ?? null;
        $product['product_orig_price'] = $request->cost_price ?? null;
        $product['product_unlimited'] = $request->unlimited ?? null;
        $product['is_surcharge'] = $request->is_surcharge ?? null;
        $product['created_by'] = 'api';
        $product['channel'] = 'web';
        $product['groupid']  = $groupid;

        DB::beginTransaction();
        try {
            $res = Product::create($product);
        DB::commit();
            return MyHelper::response(true,'Create Product successfully', ['product_code' => $res->product_code],200);
        } catch (\Exception $ex) {
        DB::rollback();
            Log::channel('product_history')->info('Create products failed',['status' => 404, 'message'=>$ex->getMessage()]);
            return MyHelper::response(false,$ex->getMessage(), [],404);
        }
    }

    /**
    * @OA\Put(
    *     path="/api/v3/product/{$productCode}",
    *     tags={"Product"},
    *     summary="Update product by productCode",
    *     description="<h2>This API will update a product by productCode and the value json form below</h2><br><code>Press try it out button to modified</code>",
    *     operationId="update",
    *     @OA\Parameter(
    *       name="productCode",
    *       in="path",
    *       description="<table id='my-custom-table'>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <td><b id='require'>Required</b></td>
                </tr>
                <tr>
                    <th>branch</th>
                    <td>The branch product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>product_is_active</th>
                    <td>Product active (no = 0, yes = 1)</td>
                    <td>false (default = 1)</td>
                </tr>
                <tr>
                    <th>product_name</th>
                    <td>The name of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>product_barcode</th>
                    <td>Product barcode</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>product_stock</th>
                    <td>Product stock</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>product_expire</th>
                    <td>Date expire of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>category_id</th>
                    <td>Category of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>unit_id</th>
                    <td>Unit of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>price</th>
                    <td>The price of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>cost_price</th>
                    <td>The cost of product</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>unlimited</th>
                    <td>Allow to sell when this product is out of stock</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>is_surcharge</th>
                    <td>Is there any extra charge for the product? (no = 0,yes = 1)</td>
                    <td>false</td>
                </tr>
            </table><br><code>Click Schema to view data property</code>",
    *       required=true,
    *     ),
    *     @OA\RequestBody(
    *       required=true,
    *       @OA\JsonContent(
    *         required={"branch","product_code","product_name","product_is_active","category_id","unit_id"},
    *         @OA\Property(property="branch", type="string", example="1"),
    *         @OA\Property(property="product_code", type="string", example="#123"),
    *         @OA\Property(property="product_is_active", type="string", example="1"),
    *         @OA\Property(property="product_name", type="string", example="IP 11 Promax"),
    *         @OA\Property(property="product_barcode", type="string", example="0123"),
    *         @OA\Property(property="product_stock", type="string", example="12"),
    *         @OA\Property(property="product_expire", type="string", example="2"),
    *         @OA\Property(property="category_id", type="string", example="132"),
    *         @OA\Property(property="unit_id", type="string", example="1"),
    *         @OA\Property(property="price", type="string", example="200,000,000"),
    *         @OA\Property(property="cost_price", type="string", example="100,000,000"),
    *         @OA\Property(property="unlimited", type="string", example="1"),
    *         @OA\Property(property="is_surcharge", type="string", example="1"),
    *       ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Update product Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="true"),
    *           @OA\Property(property="message", type="string", example="Create product Successfully"),
    *           @OA\Property(property="data",type="string", example="[]"),
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Update failed",
    *         @OA\JsonContent(
    *           @OA\Property(property="status", type="boolean", example="false"),
    *           @OA\Property(property="message", type="string", example="Product do not exists"),
    *           @OA\Property(property="data",type="string", example="[]"),
    *         )
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function update(ProductRequest $request,$prod_code)
    {
        $product = new Product;
        $product->setDeleteColumn('product_is_active');
        $product->setSelectColumn('product_code');
        $product->setDeleteValue('1');
        $product = $product->showOne($prod_code);

        if (!$product) {
            return MyHelper::response(false,'Product not found',[],404);    
        }
        $groupid = auth::user()->groupid;
        // check branch_id

        if(isset($request->branch)){
            $check_branch = Branch::where([ ['groupid',$groupid],['id',$request->branch] ])->first();
            if (!$check_branch) {
                return MyHelper::response(false,'Branch do not exist',[],403);
            }
        }
        // check unit_id
        if (isset($request->unit_id)) {
            $check_unit = ProductUnit::where([ ['groupid',$groupid],['id',$request->unit_id] ])->first();
            if (!$check_unit) {
                return MyHelper::response(false,'Product unit do not exist',[],403);            
            }
        }

        // check category_id
        if (isset($request->category_id)) {
            $check_cate = ProductCategory::where([ ['groupid',$groupid],['id',$request->category_id] ])->first();
            
            if (!$check_cate) {
                return MyHelper::response(false,'Product category do not exist',[],403);
            }
        }


        $product->branch_id = $request->branch ?? $product['branch_id'];
        $product->product_unit_id = $request->unit_id ?? $product['product_unit_id'];
        $product->product_category_id = $request->category_id ?? $product['product_category_id'];
        $product->product_code = $request->product_code ?? $product['product_code'];
        $product->product_is_active = $request->product_is_active ?? $product['product_is_active'];
        $product->product_name = $request->product_name ?? $product['product_name'];
        $product->product_full_name = $request->product_name ?? $product['product_name'];
        $product->product_barcode = $request->product_barcode ?? null;
        $product->product_stock = $request->product_stock ?? null;
        $product->product_day_expire = $request->product_expire ?? null;
        $product->product_price = $request->price ?? $product['product_price'];
        $product->product_orig_price = $request->cost_price ?? $product['product_orig_price'];
        $product->product_unlimited = $request->unlimited ?? null;
        $product->is_surcharge = $request->is_surcharge ?? null;
        $product->updated_at = date('Y-m-d H:i:s');
        $product->updated_by = auth::user()->id;

        DB::beginTransaction();
        try {
            $product->save();
        DB::commit();
            return MyHelper::response(true,'Update Product successfully', [],200);
        } catch (\Exception $ex) {
        DB::rollback();
            Log::channel('product_history')->info('Update products failed',['status' => 403, 'message'=>$ex->getMessage()]);
            return MyHelper::response(false,$ex->getMessage(), [],403);
        }
    }

    /**
    * @OA\Delete(
    *     path="/api/v3/product/{productCode}",
    *     tags={"Product"},
    *     summary="Delete a product by productCode",
    *     description="<h2>This API will delete a product by productCode</h2>",
    *     operationId="destroy",
    *     @OA\Parameter(
    *         name="productCode",
    *         in="path",
    *         example=1,
    *         description="<h4>This is the id of the product you need delete</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         required=true,
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Delete product successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Delete product successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Product not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="Product not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     security={
    *         {"bearer_token": {}}
    *     },
    * )
    */
    public function destroy($prod_code)
    {   
        $product = new Product;
        $product->setDeleteColumn('product_is_active');
        $product->setSelectColumn('product_code');
        $product->setDeleteValue('1');
        $product = $product->showOne($prod_code);
        if (!$product) {
            return MyHelper::response(false,'Product Not Found', [],404);
        }else{
            $product->delete();
        }
        return MyHelper::response(true,'Delete Product Successfully', [],200);
    }

}
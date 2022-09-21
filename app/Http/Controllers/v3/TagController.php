<?php

namespace App\Http\Controllers\v3;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TagModel;
use App\Http\Functions\CheckField;
use App\Http\Functions\MyHelper;
use Auth;
use DB;
class TagController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
    * @OA\Get(
    *     path="/api/v3/tag",
    *     tags={"tag"},
    *     summary="get all tag",
    *     description="<h2>This API will get all tag</h2>",
    *     operationId="show",
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
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="string", example="1"),
    *             @OA\Property(property="groupid",type="string", example="2"),
    *             @OA\Property(property="name",type="string", example="this is example ticket"),
    *             @OA\Property(property="color",type="string", example="example content"),
    *             @OA\Property(property="createby",type="string", example="3"),
    *             @OA\Property(property="get_tickets_detail",type="array", 
    *               @OA\Items(type="object",
    *                 @OA\Property(property="id",type="string", example="1"),
    *                   @OA\Property(property="title",type="string", example="this is title example"),
    *                   @OA\Property(property="content",type="string", example="this is content example"),
    *                 ),
    *               ),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Invalid tags",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="boolean", example="chats not found"),
    *              @OA\Property(property="data", type="string", example="[]"),
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
            $checkFileds= CheckField::check_fields($req,'tags');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds= CheckField::check_order($req,'tags');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
           }
           if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds= CheckField::CheckSearch($req,'tags');
             if($checkFileds){
                return MyHelper::response(false,$checkFileds,[],404);
             }
             $checksearch= CheckField::check_exist_of_value($req,'tags');

             if($checksearch){
                return MyHelper::response(false,$checksearch,[],404);
             }
           }    
        $chat = new TagModel;
        $chat  = $chat->getListDefault($req);
        return MyHelper::response(true,'Successfully',$chat,200);
    }

        /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

         /**
    * @OA\Get(
    *     path="/api/v3/tag/{tagId}",
    *     tags={"tag"},
    *     summary="Find tag by tagId",
    *     description="<h2>This API will find tag by {tagId} and return only a single record</h2>",
    *     operationId="show",
    *     @OA\Parameter(
    *         name="tagId",
    *         in="path",
    *         description="<h4>This is the id of the tag you are looking for</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         example=1,
    *         required=true,
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successfully",
    *         @OA\JsonContent(
    *           @OA\Property(property="data",type="object",
    *             @OA\Property(property="id",type="string", example="1"),
    *             @OA\Property(property="groupid",type="string", example="2"),
    *             @OA\Property(property="title",type="string", example="this is example ticket"),
    *             @OA\Property(property="content",type="string", example="example contetnt"),
    *             @OA\Property(property="public",type="string", example="public string"),
    *           ),
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Invalid tag ID",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="boolean", example="tag not found"),
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
        $Tags=new TagModel;
        $Tags=$Tags->ShowOne($id);
        if(!$Tags){
            return MyHelper::response(false,'quick chat not found',[],404);
        }
        return MyHelper::response(true,'Successfully',[$Tags],200);
    }

    public function store(Request $request){
        $user_id=auth::user()->id;
        $group_id=auth::user()->groupid;
        $name=$request->name ;
        $color=$request->color;
        $type=$request->type;
        $datecreate=time();
        DB::beginTransaction();
        try {
        DB::commit();
        $Tags=new TagModel;
        $Tags->name=$name;
        $Tags->color=$color;
        $Tags->createby=$user_id;
        $Tags->groupid=$group_id;
        $Tags->datecreate=$datecreate;
        $Tags->type=$type;
        $Tags->save();
        return MyHelper::response(true,'Created Tag successfully',['id'=>$Tags->id],200);
    } catch (\Exception $ex) {
        DB::rollback();
        return MyHelper::response(false,$ex->getMessage(), [],403);
    }
    }

        /**
    * @OA\Put(
    *     path="/api/v3/tag/{tagId}",
    *     tags={"tag"},
    *     summary="Update tag by tagId",
    *     description="<h2>This API will update a tag by tagIde and the value json form below</h2><br><code>Press try it out button to modified</code>",
    *     operationId="update",
    *     @OA\Parameter(
    *       name="tagId",
    *       in="path",
    *       description="<table id='my-custom-table'>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <td>false</td>
                </tr>
                <tr>
                    <th>color</th>
                    <td>color of tagt</td>
                    <td>false</td>
                </tr>
                <tr>
                    <th>type</th>
                    <td>type of tag</td>
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
    
    public function update($id,Request $request)
    {

       $tag_update = TagModel::find($id);
       $Tagsdata=TagModel::where('id',$id)->first();
       $req=$request;
       if(!$tag_update){
        return MyHelper::response(false,'Tags not found',[],404);
       }
       $tag_update->name=isset($req['name'])&&rtrim($req['name']) !=='' ? $req['name'] : $Tagsdata->name;
       $tag_update->color=isset($req['color'])&&rtrim($req['color']) !=='' ? $req['color'] : $Tagsdata->color;
       $tag_update->type=isset($req['type'])&&rtrim($req['type']) !=='' ? $req['type'] : $Tagsdata->type;
       $tag_update->save();
    return MyHelper::response(true,'updated Tags successfully',[],200);
    }

      /**
    * @OA\Delete(
    *     path="/api/v3/tag/{tagID}",
    *     tags={"tag"},
    *     summary="Delete a tag by tagID",
    *     description="<h2>This API will delete atag by tagID</h2>",
    *     operationId="destroy",
    *     @OA\Parameter(
    *         name="tagID",
    *         in="path",
    *         example=1,
    *         description="<h4>This is the id of the tag you need delete</h4>
              <code>Type: <b id='require'>Number</b></code>",
    *         required=true,
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Delete tag successfully",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="true"),
    *              @OA\Property(property="message", type="string", example="Delete tag successfully"),
    *              @OA\Property(property="data", type="string", example="[]"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="tag not found",
    *         @OA\JsonContent(
    *              @OA\Property(property="status", type="boolean", example="false"),
    *              @OA\Property(property="message", type="string", example="tag not found"),
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
        $Tags = TagModel::find($id);
        if (!$Tags) {
            return MyHelper::response(false,'Tag Not Found', [],404);
        }else{
            $Tags->delete();
        }
        return MyHelper::response(true,'Delete Tag Successfully', [],200);
    }
}
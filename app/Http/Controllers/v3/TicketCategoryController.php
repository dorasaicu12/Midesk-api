<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use App\Http\Functions\CheckField;
use App\Http\Functions\MyHelper;
use App\Models\Order;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Auth;
use DB;
use Illuminate\Http\Request;

/**
 * @group  Product Management
 *
 * APIs for managing product
 */

class TicketCategoryController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v3/ticketCategory",
     *     tags={"Ticket Category"},
     *     summary="Get list category of ticket",
     *     description="<h2>This API will Get list category of ticket with condition below</h2>",
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
     *         example="parent<=>0",
     *         description="<h4>Find records with condition get result desire</h4>
    <code>Type: <b id='require'>String<b></code><br>
    <code>Seach type supported with <b id='require'><(like,=,!=)></b> </code><br>
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
     *         example="id,name,parent",
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
     *                   @OA\Property(property="id",type="string", example="109"),
     *                   @OA\Property(property="name",type="string", example="Yêu cầu"),
     *                   @OA\Property(property="parent",type="string", example="0"),
     *                   @OA\Property(property="parent2",type="string", example="109"),
     *                   @OA\Property(property="level",type="string", example="1"),
     *                 ),
     *                 @OA\Property(property="current_page",type="string", example="1"),
     *                 @OA\Property(property="first_page_url",type="string", example="null"),
     *                 @OA\Property(property="next_page_url",type="string", example="null"),
     *                 @OA\Property(property="last_page_url",type="string", example="null"),
     *                 @OA\Property(property="prev_page_url",type="string", example="null"),
     *                 @OA\Property(property="from",type="string", example="1"),
     *                 @OA\Property(property="to",type="string", example="1"),
     *                 @OA\Property(property="total",type="string", example="1"),
     *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/ticketCategory"),
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
            $checkFileds = CheckField::check_fields($req, 'ticket_category');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds = CheckField::check_order($req, 'ticket_category');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds = CheckField::CheckSearch($req, 'ticket_category');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
            $checksearch = CheckField::check_exist_of_value($req, 'ticket_category');

            if ($checksearch) {
                return MyHelper::response(false, $checksearch, [], 404);
            }
        }
        $category = new TicketCategory();
        $category = $category->setDeleteColumn('is_show');
        $category = $category->setDeleteValue([null, 1]);
        $category = $category->getListDefault($req);
        foreach ($category as $list) {
            if (isset($list['dateupdate'])) {
                $list['dateupdate'] = strtotime($list['dateupdate']);
            }
        }
        return MyHelper::response(true, 'Successfully', $category, 200);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\POST(
     *     path="/api/v3/ticketCategory",
     *     tags={"Ticket Category"},
     *     summary="Create a Ticket Category",
     *     description="<h2>This API will Create a Ticket Category with json form below</h2><br><code>Press try it out button to modified</code>",
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
    <th>type</th>
    <td>type of ticket category</td>
    <td>true</td>
    </tr>
    <tr>
    <th>parent</th>
    <td>parent of ticket category</td>
    <td>true</td>
    </tr>
    <tr>
    <th>level</th>
    <td>Channel sent request</td>
    <td>false (default = api)</td>
    </tr>
    </table><br><code>Click Schema to view data property</code>",
     *       @OA\JsonContent(
     *         required={"title","content"},
     *         @OA\Property(property="name", type="string", example="Phiếu khiếu nại 2"),
     *         @OA\Property(property="type", type="string", example="add"),
     *         @OA\Property(property="parent", type="string", example="123456"),
     *         @OA\Property(property="level", type="string", example="3"),
     *         @OA\Property(property="sort", type="string", example="1"),
     *       ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Create Ticket Successfully",
     *         @OA\JsonContent(
     *           @OA\Property(property="status", type="boolean", example="true"),
     *           @OA\Property(property="message", type="string", example="Create Ticket Successfully"),
     *           @OA\Property(property="data",type="object",
     *             @OA\Property(property="id",type="string", example="1"),
     *           ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Create failed",
     *         @OA\JsonContent(
     *           @OA\Property(property="status", type="boolean", example="true"),
     *           @OA\Property(property="message", type="string", example="The given data was invalid"),
     *           @OA\Property(property="errors",type="object",
     *             @OA\Property(property="title",type="array",
     *               @OA\Items(type="string", example="the title field is required")
     *             ),
     *           )
     *         ),
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     },
     * )
     */
    public function store(Request $request)
    {
        $groupid = auth::user()->groupid;
        $creby = auth::user()->id;
        $time = time();
        $name = $request->name;
        $level = $request->level;
        $parent = $request->parent;
        $type = 'add';
        if ($level == 1) {
            $parent = 0;
        }
        if ($parent !== 0) {
            $checkparent = (new TicketCategory)->checkExist($parent);
            if (!$checkparent) {
                return MyHelper::response(false, 'parent of ticket category not found', [], 404);
            }
        }
        if (isset($level)) {
            if ($level == 2) {
                $parent = $request->parent;
                $TicketCheck = TicketCategory::where('id', $parent)->first();
                if (!$TicketCheck) {
                    return MyHelper::response(false, 'Category ' . $parent . ' not found', [], 404);
                }
                if ($TicketCheck->level == 3) {
                    return MyHelper::response(false, 'Category ' . $parent . ' level 3 cannot be parent of level 2', [], 404);
                } elseif ($TicketCheck->level == 2) {
                    return MyHelper::response(false, 'Category ' . $parent . ' level 2 can not be parent of each others', [], 401);
                }
                $parent2 = $parent;
            } elseif ($level == 3) {
                $parent = $request->parent;
                $TicketCheck = TicketCategory::where('id', $parent)->first();
                if (!$TicketCheck) {
                    return MyHelper::response(false, 'Category ' . $parent . ' not found', [], 404);
                }
                if ($TicketCheck->level == 1) {
                    return MyHelper::response(false, 'Category ' . $parent . ' level 1 cannot be parent of level 3', [], 404);
                } elseif ($TicketCheck->level == 3) {
                    return MyHelper::response(false, 'Category ' . $parent . ' level 3 can not be parent of each others', [], 401);
                }
                $parent2 = $TicketCheck->parent . ',' . $parent;
            }
        }
        DB::beginTransaction();
        try {
            DB::commit();
            $order = new TicketCategory;
            $order->groupid = $groupid;
            $order->name = $name;
            $order->type = $type;
            $order->level = $level;
            $order->parent = $parent;
            $order->createby = $creby;
            $order->datecreate = $time;
            $order->save();
            if ($level !== 1) {
                $category = $order->showOne($order->id);
                $category->parent2 = $parent2 . ',' . $category->id;
                $category->save();
            }
            if ($level == 1) {
                $category = $order->showOne($order->id);
                $category->parent2 = $order->id;
                $category->save();
            }
            return MyHelper::response(true, 'Created ticket category successfully', ['id' => $order->id], 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false, $ex->getMessage(), [], 403);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Get(
     *     path="/api/v3/ticketCategory/{CategoryId}",
     *     tags={"Ticket Category"},
     *     summary="Find Ticket Category by CategoryId",
     *     description="<h2>This API will find Ticket Category by {CategoryId} and return only a single record</h2>",
     *     operationId="show",
     *     @OA\Parameter(
     *         name="CategoryId",
     *         in="path",
     *         description="<h4>This is the id of the Ticket Category you are looking for</h4>
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
     *             @OA\Property(property="title",type="string", example="this is example category"),
     *             @OA\Property(property="content",type="string", example="example contetnt"),
     *             @OA\Property(property="public",type="string", example="public string"),
     *           ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Invalid Ticket Category ID",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="boolean", example="Ticket Category not found"),
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
        $TicketCategory = (new TicketCategory)->checkExist($id);
        if (!$TicketCategory) {
            return MyHelper::response(false, 'ticket category not found', [], 404);
        } else {
            $TicketCategory->dateupdate = strtotime($TicketCategory->dateupdate);
            return MyHelper::response(true, 'successfully', $TicketCategory, 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Put(
     *     path="/api/v3/ticketCategory/{CategoryId}",
     *     tags={"Ticket Category"},
     *     summary="Update Ticket Category by CategoryId",
     *     description="<h2>This API will update a Ticket Category by CategoryId and the value json form below</h2><br><code>Press try it out button to modified</code>",
     *     operationId="update",
     *     @OA\Parameter(
     *       name="CategoryId",
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
     *         @OA\Property(property="name", type="string", example="example name"),
     *         @OA\Property(property="type", type="string", example="add"),
     *         @OA\Property(property="parent", type="string", example="46077"),
     *         @OA\Property(property="level", type="string", example="3"),
     *         @OA\Property(property="sort", type="string", example="0"),
     *         @OA\Property(property="is_show", type="string", example="1"),
     *
     *       ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Update Ticket Category Successfully",
     *         @OA\JsonContent(
     *           @OA\Property(property="status", type="boolean", example="true"),
     *           @OA\Property(property="message", type="string", example="Update Ticket Category Successfully"),
     *           @OA\Property(property="data",type="string", example="[]"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Update failed",
     *         @OA\JsonContent(
     *           @OA\Property(property="status", type="boolean", example="false"),
     *           @OA\Property(property="message", type="string", example="Ticket Category do not exists"),
     *           @OA\Property(property="data",type="string", example="[]"),
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     },
     * )
     */
    public function update($id, Request $request)
    {
        $TicketCategory = (new TicketCategory)->checkExist($id);
        $update = TicketCategory::find($id);
        if (!$TicketCategory) {
            return MyHelper::response(false, 'TicketCategory Not Found', [], 404);
        } else {
            $time = time();
            $name = $request->name;
            if (isset($request->level)) {
                $level = $request->level;
            } else {
                $level = $TicketCategory->level;
            }
            $sort = $request->sort;
            $type = $request->type && 'all';
            $is_Show = $request->is_Show;
            if (isset($level)) {
                if ($level == 1) {
                    $parent = 0;
                    $parent = $id;
                } elseif ($level == 2) {
                    $parent = $request->parent;
                    $TicketCheck = TicketCategory::where('id', $parent)->first();
                    if (!$TicketCheck) {
                        return MyHelper::response(false, 'Category ' . $parent . ' not found', [], 404);
                    }
                    if ($TicketCheck->level == 3) {
                        return MyHelper::response(false, 'Category ' . $parent . ' level 3 cannot be parent of level 2', [], 404);
                    } elseif ($TicketCheck->level == 2) {
                        return MyHelper::response(false, 'Category ' . $parent . ' level 2 can not be parent of each others', [], 401);
                    }
                    $parent2 = $parent . ',' . $id;
                } elseif ($level == 3) {
                    $parent = $request->parent;
                    $parentCheck = explode(',', $parent);
                    $TicketCheck = TicketCategory::where('id', $parent)->first();
                    if (!$TicketCheck) {
                        return MyHelper::response(false, 'Category ' . $parent . ' not found', [], 404);
                    }
                    if ($TicketCheck->level == 1) {
                        return MyHelper::response(false, 'Category ' . $parent . ' level 1 cannot be parent of level 3', [], 401);
                    } elseif ($TicketCheck->level == 3) {
                        return MyHelper::response(false, 'Category ' . $parent . ' level 3 can not be parent of each others', [], 401);
                    }
                    $parent2 = $TicketCheck->parent . ',' . $parent . ',' . $id;
                }
            }
            $update->name = $name ?? $TicketCategory->name;
            $update->type = $type ?? $TicketCategory->type;
            $update->level = $level ?? $TicketCategory->level;
            $update->parent = $parent ?? $TicketCategory->parent;
            $update->parent2 = $parent2 ?? $TicketCategory->parent2;
            $update->is_Show = $is_Show ?? $TicketCategory->is_Show;
            $update->save();
            return MyHelper::response(true, 'update ticket TicketCategory successfully', [], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Delete(
     *     path="/api/v3/ticketCategory/{CategoryId}",
     *     tags={"Ticket Category"},
     *     summary="Delete a Ticket Category by CategoryId",
     *     description="<h2>This API will delete a Ticket Category by CategoryId</h2>",
     *     operationId="destroy",
     *     @OA\Parameter(
     *         name="CategoryId",
     *         in="path",
     *         example=1,
     *         description="<h4>This is the CategoryId of the ticketCategory you need delete</h4>
    <code>Type: <b id='require'>Number</b></code>",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delete ticketCategory successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="true"),
     *              @OA\Property(property="message", type="string", example="Delete ticketCategory successfully"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="ticketCategory not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="string", example="ticket not found"),
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
        $TicketCategory = (new TicketCategory)->checkExist($id);
        if (!$TicketCategory) {
            return MyHelper::response(false, 'TicketCategory Not Found', [], 404);
        } else {
            $level = $TicketCategory->level;
            $parent = $TicketCategory->parent;
            if ($level == 2) {
                $TicketCategory->delete();
                $value = TicketCategory::where('parent', $parent)->where('level', 2)->get()->pluck('id')->toArray();
                $parents = implode(',', $value);
                $categoryChange = TicketCategory::where('parent', $parent)->where('level', 3)->update(['parent2' => $parents]);
            } else {
                $TicketCategory->delete();
            }
        }
        return MyHelper::response(true, 'Delete TicketCategory Successfully', [], 200);
    }
}
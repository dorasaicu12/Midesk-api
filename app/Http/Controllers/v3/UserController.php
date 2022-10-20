<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use App\Http\Functions\CheckField;
use App\Http\Functions\MyHelper;
use App\Http\Requests\UserRequest;
use App\Models\Agent;
use App\Models\UserType;
use App\Traits\ProcessTraits;
use Auth;
use DB;
use Illuminate\Http\Request;

/**
 * @group  agents Management
 *
 * APIs for managing agents
 */
class UserController extends Controller
{
    use ProcessTraits;
    /**
     * @OA\Get(
     *     path="/api/v3/agent",
     *     tags={"Agent"},
     *     summary="Get list agent",
     *     description="<h2>This API will Get list agent with condition below</h2>",
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
     *         example="fullname<like>Nguyễn",
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
     *         example="fullname,phone,email",
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
     *                   @OA\Property(property="firstname",type="string", example="văn A"),
     *                   @OA\Property(property="lastname",type="string", example="Nguyễn"),
     *                   @OA\Property(property="phone",type="string", example="0987654321"),
     *                   @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
     *                   @OA\Property(property="address",type="string", example="212/123b"),
     *                 ),
     *                 @OA\Property(property="current_page",type="string", example="1"),
     *                 @OA\Property(property="first_page_url",type="string", example="null"),
     *                 @OA\Property(property="next_page_url",type="string", example="null"),
     *                 @OA\Property(property="last_page_url",type="string", example="null"),
     *                 @OA\Property(property="prev_page_url",type="string", example="null"),
     *                 @OA\Property(property="from",type="string", example="1"),
     *                 @OA\Property(property="to",type="string", example="1"),
     *                 @OA\Property(property="total",type="string", example="1"),
     *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/agent"),
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
            $checkFileds = CheckField::check_fields($req, 'table_users');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds = CheckField::check_order($req, 'table_users');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds = CheckField::CheckSearch($req, 'table_users');
            if ($checkFileds) {
                return MyHelper::response(false, $checkFileds, [], 404);
            }
            $checksearch = CheckField::check_exist_of_value($req, 'table_users');
            if ($checksearch) {
                return MyHelper::response(false, $checksearch, [], 404);
            }
        }
        $agents = new Agent();
        $agents = $agents->setDeleteColumn('active');
        $agents = $agents->setDeleteValue(1);
        $agents = $agents->getListDefault($req);
        return MyHelper::response(true, 'Successfully', $agents, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/agent/{agentId}",
     *     tags={"Agent"},
     *     summary="Find agent by agentId",
     *     description="<h2>This API will find agent by {agentId} and return only a single record</h2>",
     *     operationId="show",
     *     @OA\Parameter(
     *         name="agentId",
     *         in="path",
     *         description="<h4>This is the code of the agent you are looking for</h4>
    <code>Type: <b id='require'>Number</b></code>",
     *         example=1,
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="true"),
     *             @OA\Property(property="message", type="string", example="Successfully"),
     *             @OA\Property(property="data",type="object",
     *                @OA\Property(property="id",type="string", example="1"),
     *                @OA\Property(property="firstname",type="string", example="văn A"),
     *                @OA\Property(property="lastname",type="string", example="Nguyễn"),
     *                @OA\Property(property="phone",type="string", example="0987654321"),
     *                @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
     *                @OA\Property(property="address",type="string", example="212/123b"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Will be return agent not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="string", example="Agent not found"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */

    public function show($userId)
    {
        $agents = new Agent();
        $agents = $agents->setDeleteColumn('active');
        $agents = $agents->setDeleteValue('1');
        $agents = $agents->showOne($userId);
        if (!$agents) {
            return MyHelper::response(false, 'Agent not found', [], 404);
        }
        return MyHelper::response(true, 'Successfully', $agents, 200);
    }

    /**
     * @OA\POST(
     *     path="/api/v3/agent",
     *     tags={"Agent"},
     *     summary="Create a agent",
     *     description="<h2>This API will Create a agent with json form below</h2><br><code>Press try it out button to modified</code>",
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
    <th>firstname</th>
    <td>The first name of agent</td>
    <td>true</td>
    </tr>
    <tr>
    <th>lastname</th>
    <td>The last name of agent</td>
    <td>true</td>
    </tr>
    <tr>
    <th>password</th>
    <td>Password (Password must contain at least one special character and number)</td>
    <td>true</td>
    </tr>
    <tr>
    <th>password_confirmation</th>
    <td>Confirm password (valid to password)</td>
    <td>true</td>
    </tr>
    <tr>
    <th>phone</th>
    <td>Phone number agent</td>
    <td>true</td>
    </tr>
    <tr>
    <th>email</th>
    <td>Email address agent</td>
    <td>true</td>
    </tr>
    <tr>
    <th>account_type</th>
    <td>Account type ( 'agent','supervisor','admin' )</td>
    <td>true</td>
    </tr>
    <tr>
    <th>perrmission</th>
    <td>Permission of agent</td>
    <td>true</td>
    </tr>
    <tr>
    <th>role</th>
    <td>Role of agent ('sales','cs','telephonist')</td>
    <td>true</td>
    </tr>
    </table><br><code>Click Schema to view data property</code>",
     *       @OA\JsonContent(
     *         required={"firstname","lastname","password","password_confirmation","phone","email","account_type","perrmission","role"},
     *         @OA\Property(property="firstname", type="string", example="Nguyễn"),
     *         @OA\Property(property="lastname", type="string", example="Văn A"),
     *         @OA\Property(property="password", type="string", example="123456"),
     *         @OA\Property(property="password_confirmation", type="string", example="123456"),
     *         @OA\Property(property="phone", type="string", example="012345564"),
     *         @OA\Property(property="email", type="string", example="abcxyz@gmail.com"),
     *         @OA\Property(property="account_type", type="string", example="agent"),
     *         @OA\Property(property="perrmission", type="string", example="1"),
     *         @OA\Property(property="role", type="string", example="cs"),
     *       ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Create Agent Successfully",
     *         @OA\JsonContent(
     *           @OA\Property(property="status", type="boolean", example="true"),
     *           @OA\Property(property="message", type="string", example="Create Agent Successfully"),
     *           @OA\Property(property="data",type="object",
     *             @OA\Property(property="agent_id",type="string", example="1"),
     *           ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Create Agent failed",
     *         @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="The given data was invalid"),
     *           @OA\Property(property="errors",type="object",
     *             @OA\Property(property="firstname",type="array",
     *               @OA\Items(type="string", example="the firstname field is required")
     *             ),
     *           )
     *         ),
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     },
     * )
     */
    public function store(UserRequest $req)
    {
        $data = array_filter($req->all());
        $groupid = auth()->user()->groupid;

        $checkAgent = Agent::where('email', $req['email'])->first();
        if ($checkAgent) {
            return MyHelper::response(false, 'agent already exist', ['id' => $checkAgent->id], 400);
        }
        if (!in_array($data['account_type'], ['admin', 'agent', 'supervisor'])) {
            return MyHelper::response(false, 'account_type do not match', [], 422);
        }
        if (!in_array($data['role'], ['sales', 'telephonist', 'cs'])) {
            return MyHelper::response(false, 'role do not match', [], 422);
        }
        $user_type_list = UserType::where([['groupid', $groupid], ['type', 'group'], ['public', 'active']])->get()->pluck('id')->toArray();
        if (!in_array($data['perrmission'], $user_type_list)) {
            return MyHelper::response(false, 'permission do not match', [], 422);
        }
        DB::beginTransaction();
        try {

            $res['lastname'] = $data['lastname'];
            $res['firstname'] = $data['firstname'];
            $res['fullname'] = $data['firstname'] . ' ' . $data['lastname'];
            $res['groupid'] = $groupid;
            $res['username'] = $data['phone'];
            $res['level'] = $data['account_type'];
            $res['phone'] = $data['phone'];
            $res['email'] = $data['email'];
            $res['password'] = md5($data['password']);
            $res['active'] = 0;
            $res['datecreate'] = time();
            $res['dateupdate'] = time();
            $res['class_staff'] = $data['role'];
            $res['user_type_id'] = $data['perrmission'];
            $res['createby'] = auth()->user()->id;

            $agent = Agent::create($res);
            DB::commit();

            return MyHelper::response(true, 'Created Agent successfully', ['agent_id' => $agent->id], 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false, $ex->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v3/agent/{$agentId}",
     *     tags={"Agent"},
     *     summary="Update agent by agentId",
     *     description="<h2>This API will update a agent by agentId and the value json form below</h2><br><code>Press try it out button to modified</code>",
     *     operationId="update",
     *     @OA\Parameter(
     *         name="agentId",
     *         in="path",
     *         example=1,
     *         description="<h4>This is the id of the agent you need update</h4>
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
    <th>firstname</th>
    <td>The first name of agent</td>
    <td>false</td>
    </tr>
    <tr>
    <th>lastname</th>
    <td>The last name of agent</td>
    <td>false</td>
    </tr>
    <tr>
    <th>password</th>
    <td>Password (Password must contain at least one special character and number)</td>
    <td>false</td>
    </tr>
    <tr>
    <th>password_confirmation</th>
    <td>Confirm password (valid to password)</td>
    <td>false</td>
    </tr>
    <tr>
    <th>phone</th>
    <td>Phone number agent</td>
    <td>false</td>
    </tr>
    <tr>
    <th>email</th>
    <td>Email address agent</td>
    <td>false</td>
    </tr>
    <tr>
    <th>account_type</th>
    <td>Account type ( 'agent','supervisor','admin' )</td>
    <td>false</td>
    </tr>
    <tr>
    <th>perrmission</th>
    <td>Permission of agent</td>
    <td>false</td>
    </tr>
    <tr>
    <th>role</th>
    <td>Role of agent ('sales','cs','telephonist')</td>
    <td>false</td>
    </tr>
    </table><br><code>Click Schema to view data property</code>",
     *       @OA\JsonContent(
     *         required={"firstname","lastname","password","password_confirmation","phone","email","account_type","perrmission","role"},
     *         @OA\Property(property="firstname", type="string", example="Nguyễn"),
     *         @OA\Property(property="lastname", type="string", example="Văn A"),
     *         @OA\Property(property="password", type="string", example="123456"),
     *         @OA\Property(property="password_confirmation", type="string", example="123456"),
     *         @OA\Property(property="phone", type="string", example="012345564"),
     *         @OA\Property(property="email", type="string", example="abcxyz@gmail.com"),
     *         @OA\Property(property="account_type", type="string", example="agent"),
     *         @OA\Property(property="perrmission", type="string", example="1"),
     *         @OA\Property(property="role", type="string", example="cs"),
     *       ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Update successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="true"),
     *              @OA\Property(property="message", type="string", example="Update agent successfully"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Will be return agent not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="string", example="Agent not found"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     },
     * )
     */
    public function update(Request $request, $id)
    {
        $data = array_filter($request->all());
        $groupid = auth()->user()->groupid;

        if (array_key_exists('account_type', $data) && !in_array($data['account_type'], ['admin', 'agent', 'supervisor'])) {
            return MyHelper::response(false, 'account_type do not match', [], 403);
        }
        if (array_key_exists('role', $data) && !in_array($data['role'], ['sales', 'telephonist', 'cs'])) {
            return MyHelper::response(false, 'role do not match', [], 403);
        }
        if (array_key_exists('perrmission', $data)) {
            $user_type_list = UserType::where([['groupid', $groupid], ['type', 'group'], ['public', 'active']])->get()->pluck('id')->toArray();
            if (!in_array($data['perrmission'], $user_type_list)) {
                return MyHelper::response(false, 'permission do not match', [], 403);
            }
        }
        $agent = Agent::find($id);
        if (!$agent) {
            return MyHelper::response(false, 'Agent not found', [], 404);
        }

        if (isset($request['email'])) {
            if ($request['email'] != $agent->email) {
                $checkAgent = Agent::where('email', $request['email'])->first();
                if ($checkAgent) {
                    return MyHelper::response(false, 'this email already exist in another agent ', ['id' => $checkAgent->id, 'email' => $checkAgent->email], 400);
                }
            }

        }
        DB::beginTransaction();
        try {
            $fullname = $agent->fullname;

            if (isset($data['firstname']) && !isset($data['lastname'])) {
                $fullname = $data['firstname'] . ' ' . $agent->lastname;
            }
            if (!isset($data['firstname']) && isset($data['lastname'])) {
                $fullname = $agent->firstname . ' ' . $data['lastname'];
            }
            if (isset($data['firstname']) && isset($data['lastname'])) {
                $fullname = $data['firstname'] . ' ' . $data['lastname'];
            }

            $res['lastname'] = $data['lastname'] ?? $agent->lastname;
            $res['firstname'] = $data['firstname'] ?? $agent->firstname;
            $res['fullname'] = $fullname;
            $res['username'] = $data['phone'] ?? $agent->phone;
            $res['level'] = $data['account_type'] ?? $agent->level;
            $res['phone'] = $data['phone'] ?? $agent->phone;
            $res['email'] = $data['email'] ?? $agent->email;
            if (isset($data['password'])) {
                $res['password'] = $data['password'] ? md5($data['password']) : $agent->password;
            }

            $res['dateupdate'] = time();
            $res['class_staff'] = $data['role'] ?? $agent->class_staff;
            $res['user_type_id'] = $data['perrmission'] ?? $agent->user_type_id;

            $agent->update($res);

            DB::commit();

            return MyHelper::response(true, 'Update Agent successfully', [], 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return MyHelper::response(false, $ex->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v3/agent/{agentId}",
     *     tags={"Agent"},
     *     summary="Delete a agent by agentId",
     *     description="<h2>This API will delete a agent by agentId</h2>",
     *     operationId="destroy",
     *     @OA\Parameter(
     *         name="agentId",
     *         in="path",
     *         example=1,
     *         description="<h4>This is the id of the agent you need delete</h4>
    <code>Type: <b id='require'>Number</b></code>",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delete agent successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="true"),
     *              @OA\Property(property="message", type="string", example="Delete agent successfully"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Agent not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="string", example="Agent not found"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     },
     * )
     */
    public function destroy($userId)
    {
        $agents = new Agent();
        $agents = $agents->setDeleteColumn('active');
        $agents = $agents->setDeleteValue('1');
        $agents = $agents->showOne($userId);
        if (!$agents) {
            return MyHelper::response(false, 'Agent not found', [], 404);
        }
        $agents->delete();
        return MyHelper::response(true, 'Delete agent successfully', [], 200);
    }
}
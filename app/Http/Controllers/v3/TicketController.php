<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use App\Http\Functions\CheckField;
use App\Http\Functions\MyHelper;
use App\Http\Requests\TicketRequest;
use App\Models\Contact;
use App\Models\GroupTable;
use App\Models\Macro;
use App\Models\Tags;
use App\Models\Team;
use App\Models\TeamStaff;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketDetail;
use App\Models\TicketPriority;
use App\Models\User;
use App\Traits\ProcessTraits;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Log;

/**
 * @group  Tickets Management
 *
 * APIs for managing tickets
 */
class TicketController extends Controller
{
    use ProcessTraits;

    public $categoryTmp = [];

    /**
     * @OA\Get(
     *     path="/api/v3/ticket",
     *     tags={"Ticket"},
     *     summary="Get list ticket",
     *     description="<h2>This API will Get list ticket with condition below</h2>",
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
     *         example="title<=>example",
     *         description="<h4>Find records with condition get result desire</h4>
    <code>Type: <b id='require'>String<b></code><br>
    <code>Seach type supported with <b id='require'><(like,=,!=,beetwen)></b> </code><br>
    <code>With type search beetwen value like this <b id='require'> created_at<<beetwen>beetwen>{$start_date}|{$end_date}</b> format (Y/m/d H:i:s) </code><br>
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
     *         example="title,status,id",
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
     *                   @OA\Property(property="ticket_id",type="string", example="1"),
     *                   @OA\Property(property="title",type="string", example="this is example ticket"),
     *                   @OA\Property(property="assign_agent",type="string", example="1"),
     *                   @OA\Property(property="requester",type="string", example="3"),
     *                   @OA\Property(property="get_tickets_detail",type="array",
     *                     @OA\Items(type="object",
     *                       @OA\Property(property="id",type="string", example="1"),
     *                       @OA\Property(property="title",type="string", example="this is title example"),
     *                       @OA\Property(property="content",type="string", example="this is content example"),
     *                     ),
     *                   ),
     *                 ),
     *                 @OA\Property(property="current_page",type="string", example="1"),
     *                 @OA\Property(property="first_page_url",type="string", example="null"),
     *                 @OA\Property(property="next_page_url",type="string", example="null"),
     *                 @OA\Property(property="last_page_url",type="string", example="null"),
     *                 @OA\Property(property="prev_page_url",type="string", example="null"),
     *                 @OA\Property(property="from",type="string", example="1"),
     *                 @OA\Property(property="to",type="string", example="1"),
     *                 @OA\Property(property="total",type="string", example="1"),
     *                 @OA\Property(property="path",type="string", example="http://api-dev2021.midesk.vn/api/v3/ticket"),
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
        $header = $request->header('Authorization');
        if (array_key_exists('fields', $req) && rtrim($req['fields']) != '') {
            $checkFileds = CheckField::check_fields($req, 'ticket');
            if ($checkFileds) {
                Log::channel('tickets_history')->info($checkFileds, ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $req]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }

        if (array_key_exists('order_by', $req) && rtrim($req['order_by']) != '') {
            $checkFileds = CheckField::check_order($req, 'ticket');
            if ($checkFileds) {
                Log::channel('tickets_history')->info($checkFileds, ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $req]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }

        if (array_key_exists('search', $req) && rtrim($req['search']) != '') {
            $checkFileds = CheckField::CheckSearch($req, 'ticket_2');
            if ($checkFileds) {
                Log::channel('tickets_history')->info($checkFileds, ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $req]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
            $checksearch = CheckField::check_exist_of_value($req, 'ticket_' . auth::user()->groupid . '');

            if ($checksearch) {
                Log::channel('tickets_history')->info('successfully', ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $req]);
                return MyHelper::response(false, $checksearch, [], 404);
            }
        }

        if (array_key_exists('search_or', $req) && rtrim($req['search_or']) != '') {
            $checkFileds = CheckField::CheckSearchOr($req, 'ticket_2');
            if ($checkFileds) {
                Log::channel('tickets_history')->info($checkFileds, ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $req]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }
        }
        if (array_key_exists('date', $req) && rtrim($req['date']) != '') {
            $checkFileds = CheckField::CheckDate($req, 'ticket_2');
            if ($checkFileds) {
                Log::channel('tickets_history')->info($checkFileds, ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $req]);
                return MyHelper::response(false, $checkFileds, [], 404);
            }

        }

        $tickets = (new Ticket)->getDefault($req, 'getTicketsDetail:id,title,content,content_system,ticket_id,status,type,private,file_name');
        // $tickets['ticket_id']='#'.$tickets['ticket_id'];
        foreach ($tickets as $val) {
            $val['ticket_id'] = '#' . $val['ticket_id'];
            $cm = TicketDetail::where('ticket_id', $val['id'])->select((new TicketDetail)->getFillable())->orderBy('datecreate', 'desc')->limit(1)->first();

            if ($cm['type'] == 'file') {
                if (isset($cm['file_multiple'])) {
                    $array = json_decode($cm['file_multiple'], true);
                    foreach ($array as $files) {
                        $result[] = $files['file_name'];
                    }
                    $text = implode(',', $result);
                    $cm['content'] = substr('File ????nh k??m l??:' . $text, 0, 110) . '...';
                } else {
                    $cm['content'] = substr('File ????nh k??m l??:' . $cm['file_name'], 0, 110) . '...';
                }
            } elseif ($cm['type'] == 'text') {
                if ($cm['content'] !== null && strlen($cm['content']) > 50) {
                    $cm['content'] = strip_tags($cm['content']);
                }
            }
            $val['get_tickets_comment'] = $cm;
        }
        Log::channel('tickets_history')->info('Ticket list retrieve successfully', ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $req]);
        return MyHelper::response(true, 'Successfully', $tickets, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/ticket/{ticketId}",
     *     tags={"Ticket"},
     *     summary="Find ticket by ticketId",
     *     description="<h2>This API will find ticket by {ticketId} and return only a single record</h2>",
     *     operationId="show",
     *     @OA\Parameter(
     *         name="ticketId",
     *         in="path",
     *         description="<h4>This is the id of the ticket you are looking for</h4>
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
     *             @OA\Property(property="ticket_id",type="string", example="1"),
     *             @OA\Property(property="title",type="string", example="this is example ticket"),
     *             @OA\Property(property="assign_agent",type="string", example="1"),
     *             @OA\Property(property="requester",type="string", example="3"),
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
     *         description="Invalid Ticket ID",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="boolean", example="Ticket not found"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function show($id, Request $request)
    {
        $ticket_var = new Ticket;
        $header = $request->header('Authorization');
        $ticket = $ticket_var->showOne($id);
        if ($ticket) {
            $ticket['ticket_id'] = '#' . $ticket['ticket_id'];
            $ticket['datecreate'] = date('Y-m-d H:i:s', $ticket['datecreate']);
            $ticket['dateupdate'] = date('Y-m-d H:i:s', $ticket['dateupdate']);
            $ticket['first_reply_time'] = date('Y-m-d H:i:s', $ticket['first_reply_time']);
            if ($ticket['tag'] != null) {
                $tags = explode(',', $ticket['tag']);
                foreach ($tags as $val) {
                    $team = Tags::where('id', $val)->get();

                    foreach ($team as $k2 => $val2) {
                        $team_infor[] = [
                            'id' => $val2['id'],
                            'name' => $val2['name'],
                        ];

                    }
                }
            } else {
                $team_infor[] = [null];
            }

            $ticket['get_tickets_detail'] = (new Ticket)->showTicketDetail($ticket['id']);
            $ticket['tags'] = $team_infor;
            Log::channel('tickets_history')->info('Successfully', ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'data' => $ticket]);
            return MyHelper::response(true, 'Successfully', $ticket, 200);
        } else {
            Log::channel('tickets_history')->info('Successfully', ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'data' => []]);
            return MyHelper::response(false, 'Ticket not found', $ticket, 404);
        }

        return MyHelper::response(true, 'Successfully', $ticket, 200);
    }
    /**
     * @OA\POST(
     *     path="/api/v3/ticket",
     *     tags={"Ticket"},
     *     summary="Create a ticket",
     *     description="<h2>This API will Create a ticket with json form below</h2><br><code>Press try it out button to modified</code>",
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
    <th>title</th>
    <td>Title of ticket</td>
    <td>true</td>
    </tr>
    <tr>
    <th>content</th>
    <td>Content of ticket</td>
    <td>true</td>
    </tr>
    <tr>
    <th>channel</th>
    <td>Channel sent request</td>
    <td>false (default = api)</td>
    </tr>
    <tr>
    <th>priority</th>
    <td>Ticket importance</td>
    <td>false</td>
    </tr>
    <tr>
    <th>category</th>
    <td>category id of ticket</td>
    <td>false</td>
    </tr>
    <tr>
    <th>contact</th>
    <td>
    Use contact id if available <br>
    <table>
    <tr>
    <th>name</th>
    <td>Name of contact</td>
    <td>true</td>
    </tr>
    <tr>
    <th>email</th>
    <td>Email of contact</td>
    <td>true if without phone</td>
    </tr>
    <tr>
    <th>phone</th>
    <td>Phone of contact</td>
    <td>true if without email</td>
    </tr>
    </table>
    </td>
    <td>false</td>
    </tr>
    </table><br><code>Click Schema to view data property</code>",
     *       @OA\JsonContent(
     *         required={"title","content"},
     *         @OA\Property(property="title", type="string", example="Phi???u khi???u n???i 2"),
     *         @OA\Property(property="content", type="string", example="N???i dung phi???u s??? 1"),
     *         @OA\Property(property="channel", type="string", example="Facebook"),
     *         @OA\Property(property="priority", type="string", example="1"),
     *         @OA\Property(property="category", type="string", example="1"),
     *         @OA\Property(property="contact", type="object", required={"name","email"},
     *           @OA\Property(property="name",type="string", example="Nguy???n v??n A"),
     *           @OA\Property(property="facebook_id",type="string", example=""),
     *           @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
     *           @OA\Property(property="phone",type="string", example="0123456789"),
     *           @OA\Property(property="zalo_id",type="string", example=""),
     *         )
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
    public function store(TicketRequest $request)
    {
        if (array_key_exists(0, $request->all())) {
            // multiple insert
            $header = $request->header('Authorization');
            foreach ($request->all() as $key => $value) {
                $this->create_or_update_ticket($value);
            }
            Log::channel('tickets_history')->info('Created Ticket Successfully', ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
            return MyHelper::response(true, 'Created Ticket Successfully', [], 200);
        } else {
            return $this->create_or_update_ticket($request, $request->all());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v3/ticket/{$ticketId}",
     *     tags={"Ticket"},
     *     summary="Update ticket by ticketId",
     *     description="<h2>This API will update a ticket by ticketId and the value json form below</h2><br><code>Press try it out button to modified</code>",
     *     operationId="update",
     *     @OA\Parameter(
     *       name="ticketId",
     *       in="path",
     *       description="<table id='my-custom-table'>
    <tr>
    <th>Name</th>
    <th>Description</th>
    <td><b id='require'>Required</b></td>
    </tr>
    <tr>
    <th>title</th>
    <td>Title of ticket</td>
    <td>false</td>
    </tr>
    <tr>
    <th>content</th>
    <td>Content of ticket</td>
    <td>false</td>
    </tr>
    <tr>
    <th>channel</th>
    <td>Channel sent request</td>
    <td>false</td>
    </tr>
    <tr>
    <th>priority</th>
    <td>Ticket importance</td>
    <td>false</td>
    </tr>
    <tr>
    <th>category</th>
    <td>category id of ticket</td>
    <td>false</td>
    </tr>
    <tr>
    <th>contact</th>
    <td>
    Use contact id if available <br>
    <table>
    <tr>
    <th>name</th>
    <td>Name of contact</td>
    <td>false</td>
    </tr>
    <tr>
    <th>facebook_id</th>
    <td>Facebook code of contact</td>
    <td>false</td>
    </tr>
    <tr>
    <th>email</th>
    <td>Email of contact</td>
    <td>true if without phone</td>
    </tr>
    <tr>
    <th>phone</th>
    <td>Phone of contact</td>
    <td>true if without email</td>
    </tr>
    </table>
    </td>
    <td>false</td>
    </tr>
    </table><br><code>Click Schema to view data property</code>",
     *       required=true,
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *         required={"title","content"},
     *         @OA\Property(property="title", type="string", example="Phi???u khi???u n???i 2"),
     *         @OA\Property(property="content", type="string", example="N???i dung phi???u s??? 1"),
     *         @OA\Property(property="channel", type="string", example="Facebook"),
     *         @OA\Property(property="priority", type="string", example="1"),
     *         @OA\Property(property="category", type="string", example="1"),
     *         @OA\Property(property="contact", type="object", required={"name","email"},
     *           @OA\Property(property="name",type="string", example="Nguy???n v??n A"),
     *           @OA\Property(property="facebook_id",type="string", example=""),
     *           @OA\Property(property="email",type="string", example="abcxyz@gmail.com"),
     *           @OA\Property(property="phone",type="string", example="0123456789"),
     *           @OA\Property(property="zalo_id",type="string", example=""),
     *         ),
     *       ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Update successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="true"),
     *              @OA\Property(property="message", type="string", example="Update ticket successfully"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="If $ticketId do not exist or invalid will be return ticket not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="false"),
     *              @OA\Property(property="message", type="string", example="Ticket not found"),
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
        return $this->create_or_update_ticket($request, $request->all(), $id);
    }
    /**
     * @OA\Delete(
     *     path="/api/v3/ticket/{ticketId}",
     *     tags={"Ticket"},
     *     summary="Delete a ticket by ticketId",
     *     description="<h2>This API will delete a ticket by ticketId</h2>",
     *     operationId="destroy",
     *     @OA\Parameter(
     *         name="ticketId",
     *         in="path",
     *         example=1,
     *         description="<h4>This is the id of the ticket you need delete</h4>
    <code>Type: <b id='require'>Number</b></code>",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delete ticket successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example="true"),
     *              @OA\Property(property="message", type="string", example="Delete ticket successfully"),
     *              @OA\Property(property="data", type="string", example="[]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="ticket not found 12",
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
    public function destroy($id, Request $request)
    {
        $ticket = (new Ticket)->showOne($id);
        $header = $request->header('Authorization');
        if (!$ticket) {
            Log::channel('tickets_history')->info('Ticket Not Found', ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
            return MyHelper::response(false, 'Ticket Not Found', [], 404);
        } else {
            unset($ticket->requester_info);

            $ticket->is_delete = 1;
            $ticket->is_delete_date = date('Y-m-d H:i:s');
            $ticket->is_delete_creby = auth::user()->id;
            $ticket->save();
        }
        Log::channel('tickets_history')->info('Delete Ticket Successfully', ['client' => ['authorization' => $header, 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
        return MyHelper::response(true, 'Delete Ticket Successfully', [], 200);
    }

/**
 * @OA\POST(
 *     path="/api/v3/ticket/comment/{$ticketId}",
 *     tags={"Ticket"},
 *     summary="Create a new comment inside a ticket by ticketId",
 *     description="<h2>This API will create a comment in a ticket by ticketId and the value json form below</h2><br><code>Press try it out button to modified</code>",
 *     operationId="comment",
 *     @OA\Parameter(
 *       name="ticketId",
 *       in="path",
 *       description="<table id='my-custom-table'>
<tr>
<th>Name</th>
<th>Description</th>
<td><b id='require'>Required</b></td>
</tr>
<tr>
<th>private</th>
<td>(0: normal, 1: internal note)</td>
<td>true</td>
</tr>
<tr>
<th>content</th>
<td>Content of comment</td>
<td>true</td>
</tr>
</table><br><code>Click Schema to view data property</code>",
 *       required=true,
 *     ),
 *     @OA\RequestBody(
 *       required=true,
 *       @OA\JsonContent(
 *         required={"private","content"},
 *         @OA\Property(property="content", type="string", example="Content ticket num 1"),
 *         @OA\Property(property="private", type="string", example="0"),
 *       ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Create a comment successfully",
 *         @OA\JsonContent(
 *              @OA\Property(property="status", type="boolean", example="true"),
 *              @OA\Property(property="message", type="string", example="Create a comment successfully"),
 *              @OA\Property(property="data", type="string", example="[]"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="If $ticketId do not exist or invalid will be return ticket not found",
 *         @OA\JsonContent(
 *              @OA\Property(property="status", type="boolean", example="false"),
 *              @OA\Property(property="message", type="string", example="Ticket not found"),
 *              @OA\Property(property="data", type="string", example="[]"),
 *         ),
 *     ),
 *     security={
 *         {"bearer_token": {}}
 *     },
 * )
 */

    public function comment(Request $request, $id)
    {
        if (!$id) {
            Log::channel('tickets_history')->info('Create Failed', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
            return MyHelper::response(false, 'Create Failed', [], 500);
        }
        $comment = $this->create_comment($id, $request->all(), '');
        if (!$comment) {
            Log::channel('tickets_history')->info('Ticket not found', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
            return MyHelper::response(false, 'Ticket not found', [], 404);
        }
        Log::channel('tickets_history')->info('Created Comment Successfully', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
        return MyHelper::response(true, 'Created Comment Successfully', [], 200);
    }

    /**
     * Upload File for ticket
     *
     * @urlParam  id required id of comment
     * @bodyParam file[] file required file upload for comment.

     * @response 200 {
     *   "status": true,
     *   "message": "Successfully",
     *   "data": []
     * }

     * @response 404 {
     *   "status": false,
     *   "message": "Resource Not Found",
     *   "data": []
     * }
     */
    public function attachfile(Request $request, $id)
    {
        if (!$id) {
            return MyHelper::response(false, 'Upload Failed', [], 500);
        }
        if ($request->hasFile('file')) {
            $groupTable = GroupTable::select('upload_size')->find($this->groupid);
            $uploadsize_limit = $groupTable->upload_size;
            $count_total_size = 0;
            $count_total_file = 20;
            if (count($request->file('file')) > $count_total_file) {
                return MyHelper::response(false, 'Exceed the number of files uploaded !', [], 201);
            }
            if (count($request->file('file')) > 1) {
                foreach ($request->file('file') as $file) {
                    if ($count_total_size > $uploadsize_limit) {
                        return MyHelper::response(false, 'Uploaded file exceeds the allowed size !', [], 201);
                    }
                    $count_total_size += $file->getSize() / 1024 / 1024;
                }
            } else {
                if ($request->file('file')[0]->getSize() / 1024 / 1024 > $uploadsize_limit) {
                    return MyHelper::response(false, 'Uploaded file exceeds the allowed size !', [], 201);
                }
            }
            // Call api from midesk core
            $check_storage = $this->CallApiCheckStorage($request->file('file'));
            if (!$check_storage) {
                return MyHelper::response(false, 'Uploaded file exceeds the allowed size !', [], 201);
            }
            $comment = $this->create_comment($id, $request->all(), '');
        } else {
            return MyHelper::response(false, 'File upload not found!', [], 400);
        }
        if ($comment) {
            return MyHelper::response(true, 'Upload File Successfully', [], 200);
        } else {
            return MyHelper::response(false, 'Upload File Failed', [], 201);
        }
    }

    /* function call api check storage from core */
    public function CallApiCheckStorage($req = '')
    {
        return true;
    }

    public function create_contact($data)
    {
        //Ki???m tra t???n t???i contact hay kh??ng
        $contact = new Contact;
        $phone = $data['phone'];
        $email = $data['email'];
        if (!empty($email) && !empty($phone)) {
            $check_contact = $contact->select(['id', 'address', 'email', 'phone', 'fullname'])
                ->where(function ($q) use ($phone, $email) {
                    $q->where('phone', $phone)->orWhere('email', $email);
                })->first();
        } elseif (empty($phone)) {
            $check_contact = $contact->select(['id', 'address', 'email', 'phone', 'fullname'])
                ->where('email', $email)
                ->first();
        } else {
            $check_contact = $contact->select(['id', 'address', 'email', 'phone', 'fullname'])
                ->where('phone', $phone)
                ->first();
        }
        //Th??m m???i Contact
        if (!$check_contact) {
            $contact->groupid = $this->groupid;
            $contact->fullname = $data['name'];
            $contact->phone = $data['phone'];
            $contact->email = $data['email'];
            $contact->facebook_id = $data['facebook_id'] ?? '';
            $contact->zalo_id = $data['zalo_id'];
            $contact->channel = $data['channel'];
            $contact->datecreate = $data['time'];
            $contact->creby = $data['creby'];

            $contact->save();

            if (!$contact) {
                return MyHelper::response(false, 'Create Contact Failed', [], 500);
            }
            $requester = $contact->id;
        } else {
            $requester = $check_contact->id;
        }
        return $requester;
    }

    public function ticketForm(Request $request)
    {
        $groupid = auth::user()->groupid;
        $team = TeamStaff::with('Agent')->select('team_id', 'agent_id')->where('groupid', $groupid)->get();
        $teams = [];
        foreach ($team as $element) {
            $teamid = $element['team_id'];
            unset($element['agent_id']);
            unset($element['team_id']);
            $teams[$teamid][] = [
                'id' => $element->Agent['id'],
                'fullname' => $element->Agent['fullname'],
            ];
        }
        $data['teams'] = $teams;
        $data['priority'] = TicketPriority::all()->toArray();
        $data['category'] = TicketCategory::with('Child.Child')->where([['groupid', $groupid], ['parent', '0']])->get()->toArray();
        $data['listEmail'] = User::select('email')->where('groupid', $groupid)->get()->pluck('email')->toArray();
        Log::channel('tickets_history')->info('TicketForm retrive Successfully', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
        return MyHelper::response(true, 'Successfully', $data, 200);
    }
    /**
     * @OA\Get(
     *     path="/api/v3/ticket/ticketForm",
     *     tags={"Ticket"},
     *     summary="Get sample data to create ticket",
     *     description="<h2>This API will get sample data to create tickets</h2><br><code>Press try it out button to modified</code>",
     *     operationId="ticketForm",
     *     @OA\Response(
     *         response=200,
     *         description="Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="true"),
     *             @OA\Property(property="message", type="string", example="Successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data",type="object",
     *                   @OA\Property(property="teams",type="object",
     *                     @OA\Property(property="13",type="array",
     *                       @OA\Items(type="object",
     *                         @OA\Property(property="id",type="string", example="1"),
     *                         @OA\Property(property="fullname",type="string", example="this is title example"),
     *                       ),
     *                     ),
     *                   ),
     *                   @OA\Property(property="priority",type="array",
     *                     @OA\Items(type="object",
     *                       @OA\Property(property="id",type="string", example="1"),
     *                       @OA\Property(property="name",type="string", example="Kh???n c???p"),
     *                     ),
     *                   ),
     *                   @OA\Property(property="category",type="array",
     *                     @OA\Items(type="object",
     *                       @OA\Property(property="id",type="string", example="1"),
     *                       @OA\Property(property="name",type="string", example="Y??u c???u (Enquiry)"),
     *                     ),
     *                     @OA\Items(type="object",
     *                       @OA\Property(property="id",type="string", example="2"),
     *                       @OA\Property(property="name",type="string", example="Than phi???n (Complaint)"),
     *                     ),
     *                   ),
     *                   @OA\Property(property="listEmail",type="array",
     *                     @OA\Items(type="string",example="abc@gmail.com"),
     *                   ),
     *                 ),
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function macroList(Request $request)
    {
        $groupid = auth()->user()->groupid;
        $list['priority'] = TicketPriority::all()->toArray();
        Log::channel('tickets_history')->info('macroList retrive Successfully', ['client' => ['authorization' => $request->header('Authorization'), 'Content-type' => $request->header('Accept'), 'host' => request()->getHttpHost()], 'request' => $request->all()]);
        return MyHelper::response(true, 'Successfully', $list, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/ticket/macro",
     *     tags={"Ticket"},
     *     summary="Get sample data to create ticket",
     *     description="<h2>This API will get sample data to create quick tickets</h2><br><code>Press try it out button to modified</code>",
     *     operationId="macroList",
     *     @OA\Response(
     *         response=200,
     *         description="Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="true"),
     *             @OA\Property(property="message", type="string", example="Successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data",type="object",
     *                   @OA\Property(property="priority",type="array",
     *                     @OA\Items(type="object",
     *                       @OA\Property(property="id",type="string", example="1"),
     *                       @OA\Property(property="title",type="string", example="Kh???n c???p"),
     *                       @OA\Property(property="description",type="string", example="text to test"),
     *                       @OA\Property(property="type",type="string", example="text"),
     *                       @OA\Property(property="action",type="string", example="some action"),
     *                     ),
     *                   ),
     *                 ),
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearer_token": {}}
     *     }
     * )
     */
    public function ticketMerge(Request $request, $id)
    {
        return $this->SubmitMerge($request->all(), $id);
    }

    public static function convert_from_latin1_to_utf8_recursively($dat)
    {
        if (is_string($dat)) {
            return utf8_encode($dat);
        } elseif (is_array($dat)) {
            $ret = [];
            foreach ($dat as $i => $d) {
                $ret[$i] = self::convert_from_latin1_to_utf8_recursively($d);
            }

            return $ret;
        } elseif (is_object($dat)) {
            foreach ($dat as $i => $d) {
                $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);
            }

            return $dat;
        } else {
            return $dat;
        }
    }
}

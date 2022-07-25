<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Models\Team;
use App\Models\TeamStaff;
use App\Http\Functions\MyHelper;
use Auth;
use JWTAuth;
/**
 * @group  Authentication
 *
 */
class AuthController extends Controller
{
    /**
    * Create a new AuthController instance.
    *
    * @return void
    */
    public function __construct()
    {
        $this->middleware('auth:site', ['except' => ['login']]);
    }
    /**
    * @OAS\SecurityScheme(
    *   securityScheme="bearer_token",
    *   type="https",
    *   scheme="bearer"
    * ),
    * @OA\Post(
    * path="/api/v3/auth/login",
    * summary="Login to get token",
    * description="<h2>To login to get token you need fill in your email and password </h2><br> 
    <code>Press try it out button to get token</code><br>
    <code>After receiving the token you need to copy it then you can press the Authorize button and past the token in value field</code><br>
    <code id='require'>Don't forget to put Bearer in front of the token</code><br>
    <code id='require'>Example: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9</code>",
    * operationId="authLogin",
    * tags={"Login"},
    * @OA\RequestBody(
    *    required=true,
    *    description="<code>Fill email and password below to get token</code><br><code>Click Schema to view data property</code>",
    *    @OA\JsonContent(
    *       required={"email","password"},
    *       @OA\Property(property="email", type="string", format="email", example="user2@gmail.com"),
    *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
    *    ),
    * ),
    *     @OA\Response(
    *         response=200,
    *         description="Successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="status", type="boolean", example="true"),
    *             @OA\Property(property="message", type="string", example="Successfully"),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="access_token", type="string", example="$token"),
    *                 @OA\Property(property="token_type", type="string", example="Bearer"),
    *                 @OA\Property(property="expires_in", type="number", example="300"),
    *                 @OA\Property(property="permissions",type="object",
    *                   @OA\Property(property="dashboard_cskh",type="array", 
    *                       @OA\Items(type="string", example="View"),
    *                       @OA\Items(type="string", example="Update"),
    *                       @OA\Items(type="string", example="Delete"),
    *                   ),
    *                 ),
    *                 @OA\Property(property="roles",type="object",
    *                   @OA\Property(property="name",type="string", example="Role Name"),
    *                   @OA\Property(property="type",type="array", 
    *                       @OA\Items(type="string", example="User type name")
    *                   ),
    *                 ),
    *             )
    *         )
    *     ),
    * @OA\Response(
    *    response=401,
    *    description="Unauthorized",
    *    @OA\JsonContent(
    *       @OA\Property(property="error", type="string", example="Unauthorized")
    *        )
    *     )
    * )
    */
    public function login(Request $request)
    {   
        $check_user = new User;
        $token = auth('api')->attempt($request->all());
        if ($token) {
            $user = auth()->user();
            if ($user->active == 0) {   
                return MyHelper::response(false,'Your account is locked, can\'t login',[],403);
            }
            $token = $this->respondWithToken($token)->original;
            $typeRole = $user->Roles()->get('name')->pluck('name');
            $permissions = $user->Permissions()->get(['page','action']);
            $format_permisssions = [];
            foreach ($permissions as $key => $permission) {
                $format_permisssions[$permission['page']][] = $permission['action'];
            }
            $id=$user->id;
            $team_infor=(new TeamStaff)->getTeamInfor($id);

            $token['data_user']['agent_id']=$user->id;
            $token['data_user']['email']=$user->email;
            $token['data_user']['firstname']=$user->firstname;
            $token['data_user']['lastname']=$user->lastname;
            $token['data_user']['fullname']=$user->fullname; 
            $token['data_user']['level']=$user->level;
            $token['data_user']['extension']=$user->call_extension;
            
            $token['roles']['name'] = $user->class_staff;
            $token['roles']['type'] = $typeRole;
            $token['department'] = $team_infor;
            $token['permissions'] = $format_permisssions;

            return MyHelper::response(true,'Successfully',$token,200);
        }else{
            return MyHelper::response(false,'Unauthorized',[],401);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function group()
    {
        $groupid = explode(',', auth()->user()->site_groups);
        $info_group = Group::select(['group_name','id'])->whereIn('id',$groupid)->get();
        if ($info_group) {
            return response()->json(['status' => true,'message' => 'Successfully','data' => $info_group]);
        }
        return response()->json(['status' => false,'message' => 'Failed'], 500);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL()
        ]);
    }
}
<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuthByUser;
use App\Models\Group;
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
        * Login Get Token
        ===================================
        * @bodyParam  email email required The email of the user Example: admin@gmail.com 
        * @bodyParam  password password required The password of the user Example: ***********
        ===================================
        * @response  {
        *  "access_token": "{token}",
        *  "token_type": "bearer",
        *  "expires_in": "300"
        * }
    **/
    public function login(Request $request)
    {   
        $check_user = new AuthByUser;
        $user = $check_user->Check($request->all());
        if ($user) {
            $token = JWTAuth::fromUser($user);
            $token = $this->respondWithToken($token)->original;
            return response()->json($token, 201);
        }else{
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
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

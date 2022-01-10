<?php

namespace App\Http\Controllers;

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
    * @OAS\SecurityScheme(
    *   securityScheme="bearer_token",
    *   type="https",
    *   scheme="bearer"
    * ),
    * @OA\Post(
    * path="/api/v3/auth/login",
    * summary="Get token",
    * description="Get token by email, password",
    * operationId="authLogin",
    * tags={"Login"},
    * @OA\RequestBody(
    *    required=true,
    *    description="Pass user credentials",
    *    @OA\JsonContent(
    *       required={"email","password"},
    *       @OA\Property(property="email", type="string", pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$", format="email", example="user2@gmail.com"),
    *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
    *    ),
    * ),
    * @OA\Response(
    *    response=201,
    *    description="Successful",
    *    @OA\JsonContent(
    *        @OA\Property(property="access_token", type="string", example="{$token}"),
    *        @OA\Property(property="token_type", type="string", example="bearer"),
    *        @OA\Property(property="expires_in", type="string", example="{$minute}")
    *    )
    * ),
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

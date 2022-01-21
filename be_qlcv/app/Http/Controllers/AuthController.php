<?php

namespace App\Http\Controllers;

use App\Repositories\Authentication\AuthInterface;
use App\Repositories\User\UserInterface;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;

/**
 * class use for authenticate jwt token
 */
class AuthController extends Controller
{
    public AuthInterface $authInterface;
    public UserInterface $userInterface;
    public function __construct(AuthInterface $authRepository, UserInterface $userRepository)
    {
        $this->authInterface = $authRepository;
        $this->userInterface = $userRepository;
    }

    public function verifyToken(Request $request) {
        $token = $request->token;
        try {
            JWTAuth::parseToken($token)->authenticate();
        }
        catch(Exception $e) {
            return response()->json([
                'code' => 401,
                'message' => "Xác thực không thành công",
                "token" =>$token
            ]);
        }
        return response()->json([
            'code' => 200,
            'message' => "Xác thực thành công",
            "token" =>$token
        ]);
    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(),
        [
            'email' => 'required|email|unique:users',
            'username' => 'required',
            'password'=>'required|min:6',
            'confirm_password' => 'required|min:6',
            'firstname'=>'required',
            'lastname' => 'required',
            'gender' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'fails',
                    'error' => $validator->errors(),
                    'message' => 'Đăng ký thất bại',
                    'code' => 401
                ]
            );
        }
        

        $userArray = [
            'username' => $request->username,
            'password' => $request->password,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'gender' => $request->gender,
            'is_admin' => 0,
            'avatar' => ''
        ];

        $user = $this->userInterface->create($userArray);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar');
            $avatarName = time() . '.' . $avatarPath->getClientOriginalExtension();
            $path = $request->file('avatar')->storeAs('uploads/avatar/'.$user->id, $avatarName, 'public');
            $user->avatar = '/storage/'.$path;
            return $user->avatar;
            $this->userInterface->update($user->id,['avatar'=>$user->avatar]);
        };
         

        return Response()->json(
            array(
                "status" => 'success',
                "data" => $userArray,
                'message' => 'Đăng ký thành công',
                'code' => 200
            )
        );

    }
    
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credential
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6|max:50'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validator->errors(),
                    'message'=>"Email hoặc số tài khoản hợp lệ",
                    'code' => 435
                ]
            );
        }

        //Request is validated
        
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                	'status' => 'error',
                	'message' => 'Thông tin đăng nhập chưa đúng.',
                    'code' => 400
                ]);
            }
        } catch (JWTException $e) {
            return response()->json([
                    'status' => 'error',
                	'message' => 'Không thể tạo token.',
                    'code' => 500
            ]);
        }
 	
 		//Token created, return with success response and jwt token
        return response()->json([
            'status' => "success",
            'token' => $token,
            'code' => 200,
            'data' => JWTAuth::user(),
            'message' => "Đăng nhập thành công"
        ]);
    }
 
    public function logout(Request $request)
    {
        $token = $this->bearerToken($request);

        if ($token) $user = JWTAuth::authenticate($token);
        else response()->json([
            'status' => "error",
            'code' => 400,
            'message' => "Token is empty!"
        ]);

		//Request is validated, do logout        
        try {
            JWTAuth::invalidate($request->token);
 
            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => 'Đăng xuất thành công.'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Xin lỗi, bạn không thể đăng xuất.',
                'code' => 500
            ]);
        }
    }
}

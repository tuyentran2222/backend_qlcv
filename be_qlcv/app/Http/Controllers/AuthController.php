<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
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
        $validator = Validator::make($request->all(), $this->getUserRulesValidation());

        if ($validator->fails()) {
            return Helper::getResponseJson(400, "Đăng ký thất bại", [], 'register', $validator->errors());
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
         

        return Response()->json(Helper::getResponseJson(200, "Đăng ký thành công", $userArray, 'register' , []));

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
                return Helper::getResponseJson(400, 'Thông tin đăng nhập chưa đúng.', [], 'login', []);
            }
        } catch (JWTException $e) {
            return  Helper::getResponseJson(500,"Không thể tạo token", [], 'login', []);
        }

        $dataReturn = ["token" => $token, 'data' => JWTAuth::user()];
        return Helper::getResponseJson(200, "Đăng nhập thành công", $dataReturn, 'login');
    }
 
    public function logout(Request $request)
    {
        $token = $this->bearerToken($request);

        if ($token) $user = JWTAuth::authenticate($token);
        else Helper::getResponseJson(400, "Token is empty", [], 'logout');

		//Request is validated, do logout        
        try {
            JWTAuth::invalidate($request->token);
            return Helper::getResponseJson(200, "Đăng xuất thành công.", [], 'logout');
        } catch (JWTException $exception) {
            return  Helper::getResponseJson(500, 'Xin lỗi, bạn không thể đăng xuất.', [], 'logout');
        }
    }

    public function getUserRulesValidation() {
        return 
        [
            'email' => 'required|email|unique:users',
            'username' => 'required',
            'password'=>'required|min:6',
            'confirm_password' => 'required|min:6',
            'firstname'=>'required',
            'lastname' => 'required',
            'gender' => 'required',
        ];
    }
}

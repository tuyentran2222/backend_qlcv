<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function update($id, Request $request){
       
        $file = $request->file('avatar');
        if(!is_null($file)){
            $filename = $file->getClientOriginalName().'.'.$file->extension();
            $movepath = "avt\\".$id;
            $file->move(public_path($movepath), $filename);
            $path = $movepath.'\\'.$filename;
        }

        $user = User::updateOrCreate(
            ['id' => $id],
            [
                'username' => $request->username,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'gender'  => $request->gender, 
            ]
        );

        return response()->json([
            'code' => 200,
            'data' => $user,
            'message' => "Cập nhật thành công"
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
                ],401
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
            'is_admin' => 0
        ];
        
        $user = User::create($userArray);
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar');
            $avatarName = time() . '.' . $avatarPath->getClientOriginalExtension();
       
            $path = $request->file('avatar')->storeAs('uploads/avatar/'.$user->id, $avatarName, 'public');
            $user->avatar = '/storage/'.$path;
            $user->save();
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
        //Crean token
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                	'status' => 'error',
                	'message' => 'Thông tin đăng nhập chưa đúng.',
                    'code' => 400
                ]);
            }
        } catch (JWTException $e) {
    	return $credentials;
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
 
    public function getUser(Request $request)
    {   $token = $this->bearerToken($request);
        if ($token) $user = JWTAuth::authenticate($token);
        else response()->json([
            'code' => '400',
            'status' => "error",
            'message' => "Chưa có token"
        ]);
        return response()->json([
            'data' => $user,
            'code' => 200,
            'message' => "Lấy thông tin người dùng thành công"
        ]);
    }

    public static function bearerToken($request)
    {
        $header = $request->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }
    }

    public static function user(Request $request) {
        $token = UserController::bearerToken($request);
        if ($token) $user = JWTAuth::authenticate($token);
        return $user;
    }

    public function getAllProjects(Request $request) {
        $user = UserController::user($request);
        
        $projects = DB::table('members')->join('projects', 'members.projectId', "=", 'projects.id')->get(['projectName', 'projectId']);
        return response()->json([
            'code' => 200,
            'data' => $projects,
            'message' => "Thành công"
        ]);
    }
}

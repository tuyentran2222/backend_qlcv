<?php
namespace App\Repositories\Authentication;

use App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Exception;
use Illuminate\Support\Str;
use App\Models\User;

class AuthRepository extends EloquentRepository implements AuthInterface {
    public function model(): string
    {
        return User::class;
    }

    public function getModel()
    {
        return User::class;
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
        
        $user = $this->model->create($userArray);
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
    public function getUserByToken($token) {
        return JWTAuth::authenticate($token);
    }

}

?>
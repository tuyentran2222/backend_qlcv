<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return 1;
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(),
        [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validator->errors(),
                    'description'=>"Email hoặc số tài khoản chưa chính xác",
                    'code' => 401
                ],
                401
            );
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->password
        ];
 
        if (Auth::attempt($credentials)) {
            return Response()->json(
                array(
                    'status' => 'success',
                    'description' => 'Đăng nhập thành công',
                    'code' => 200
                ),200
            );
        } else {
            return response()->json(array(
                'status' => 'error',
                'error' => $validator->errors(),
                'description' => 'Đăng nhập thất bại',
                'code' => 401
            ),401);
        }


        // $user = User::where(array('email'=>$request->email, 'password' => $request->password))->get();
        // if ($user->count() > 0) {
        //     return Response()->json(
        //         array(
        //             'status' => 'success',
        //             'description' => 'Đăng nhập thành công',
        //             'code' => 200
        //         ),200
        //     );
        // }
        // return response()->json(array(
        //     'status' => 'error',
        //     'error' => $validator->errors(),
        //     'description' => 'Đăng nhập thất bại',
        //     'code' => 401
        // ),401);

    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(),
        [
            'email' => 'required|email|unique:users',
            'username' => 'required',
            'password'=>'required|min:6',
            'confirm_password' => 'required|min:6',
            // 'firstname'=>'required',
            // 'lastname' => 'required',
            // 'gender' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'fails',
                    'error' => $validator->errors(),
                    'description' => 'Đăng ký thất bại',
                    'code' => 401
                ],401
            );
        }

        $userArray = [
            'username' => $request->username,
            'password' => $request->password,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'remember_token' => $request->token,
            'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            //'avatar'=> $request->file('UrlImage')->getClientOriginalName(),
            'avatar'=> '123',
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'gender' => $request->gender,
            'is_admin' => 0
        ];
        
        if ($request->hasFile('UrlImage')) {
            $image = $request->file('UrlImage');
            $username = $image->getClientOriginalName();
            $destinationPath = public_path('/upload/images');
            $imagePath = $destinationPath . "/" . $username;
            $image->move($destinationPath, $username);
         }
        $user = User::create($userArray);
        return Response()->json(
            array(
                "status" => 'success',
                "data" => $userArray,
                'code' => 200
            )
        );

    }
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

}

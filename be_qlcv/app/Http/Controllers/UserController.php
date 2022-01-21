<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Repositories\Authentication\AuthInterface;
use App\Repositories\User\UserInterface;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public UserInterface $userInterface;
    public AuthInterface $authInterface;
    public function __construct(UserInterface $userRepository, AuthInterface $authRepository) 
    {
        $this->userInterface = $userRepository;
        $this->authInterface = $authRepository;
    }

    public function index() {
        return $this->userInterface->index();
    }

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

    public function getUser(Request $request)
    {   
        $token = $this->bearerToken($request);
        if ($token) $user = $this->authInterface->getUserByToken($token);
        else response()->json([
            'code' => '400',
            'status' => "error",
            'message' => "Token không tồn tại."
        ]);
        return response()->json([
            'data' => $user,
            'code' => 200,
            'message' => "Lấy thông tin người dùng thành công"
        ]);
    }

    /**
     * get token send from client
     */
    public static function bearerToken($request)
    {
        $header = $request->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }
    }

    public function getAllProjects(Request $request) {
        $token = UserController::bearerToken($request);
        $user = $this->authInterface->getUserByToken($token);
        if ($user) $projects = $this->userInterface->getAllProjects($user->id ,$request);
        return response()->json([
            'code' => 200,
            'data' => $projects,
            'message' => "Thành công"
        ]);
    }
}

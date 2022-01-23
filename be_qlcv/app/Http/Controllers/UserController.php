<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Repositories\Authentication\AuthInterface;
use App\Repositories\User\UserInterface;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use App\Helpers\Helper;
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
        $action = "update user";
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

        return Helper::getResponseJson(200, "Cập nhật thành công",  $user, $action);
    }

    public function getUser(Request $request)
    {   
        $action = "get user";
        $token = $this->bearerToken($request);
        if ($token) $user = $this->authInterface->getUserByToken($token);
        else return Helper::getResponseJson(400, "Token không tồn tại.",  [], $action);
        return Helper::getResponseJson(200, "Lấy thông tin người dùng thành công",  $user, $action);
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
        $action = 'get all projects';
        $token = UserController::bearerToken($request);
        $user = $this->authInterface->getUserByToken($token);
        if ($user) $projects = $this->userInterface->getAllProjects($user->id ,$request);
        return Helper::getResponseJson(200,  "Thành công", $projects, $action);
    }
}

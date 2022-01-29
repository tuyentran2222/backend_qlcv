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
use Facade\FlareClient\Stacktrace\File;
use Illuminate\Http\File as HttpFile;

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

    public function update(Request $request){
        $user = Helper::getUser(); 
        $action = "update user";

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar');
            $avatarName = time() . '.' . $avatarPath->getClientOriginalExtension();
            //delete all files avatar before
            $files = glob(public_path().'/storage/uploads/avatar/'.$user->id.'/*'); //get all file names
            foreach($files as $file){
                if(is_file($file))
                unlink($file); //delete file
            }
            $path = $request->file('avatar')->storeAs('uploads/avatar/'.$user->id, $avatarName, 'public');
            $user->avatar = '/storage/'.$path;

            $this->userInterface->update($user->id,['avatar'=>$user->avatar]);
        };
        
        $user = User::updateOrCreate(
            ['id' => $user->id],
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

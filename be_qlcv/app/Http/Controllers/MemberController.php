<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use App\Models\User;
class MemberController extends Controller
{
    protected $project;
    protected $ownerUser;

    public function __construct()
    {
        $this->ownerUser = JWTAuth::parseToken()->authenticate();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $this->ownerUser->getId();
        $projectId = isset($request->project) ? $request->project : '';
        if (!is_numeric($projectId) || $projectId === null) 
            return response()->json([
                'status' =>'error',
                'description' => 'Id của project là một số hoặc không được để trống.',
                'code' => 400
            ]);
        $check = false;
        $project = Project::find($projectId);
        if (!$project) return response()->json([
            'code' => 404,
            'message' => "Project không tồn tại"
        ]);
        $ownerId = DB::table('projects')->where('id', $project->id)->select('ownerId')->first();
        $ownerProject = User::find($ownerId->ownerId);
        $memberArray = array();
        if ($ownerId->ownerId === $ownerProject->id) $check=true;
        $memberArray[0] = [
            'index'=> 1 ,
            'id'=> $ownerId->ownerId,
            'role' => "Chủ dự án",
            'username' =>$ownerProject->username,
            'email' => $ownerProject->email,
            'projectId' =>$projectId
        ];
            
        $member = $project->members()->get();
        $index = 1;
        foreach ($member as $m ) {
            $memberArray[$index] = DB::table('users')->select('email','username','id') -> where('id', $m->userId)->first();
            $memberArray[$index]->index = $index + 1;
            $memberArray[$index]->role ="Thành viên";
            $memberArray[$index]->projectId = $projectId;
            $index ++;
            if ($m->userId === $userId) $check = true;
        }

        if ($check)
        return response()->json([
            'status' =>'success',
            'projectId' => $projectId,
            'data' => $memberArray,
            'code' => 200
        ]);
        
        return response()->json([
            'status' =>'error',
            'description' => 'Bạn không tham gia vào project',
            'code' => 400
        ]);
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $email = $request->email;
        $ownerId = $this->ownerUser->getId();
        $projectId = isset($request->project) ? $request->project : '';

        $validator = Validator::make([
            'projectId' => $projectId,
            'email' => $email
        ],
        [
            'projectId' => 'required|integer',
            'email' => 'required|email',
        ]);
        
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validator->errors(),
                    'message'=>"Thông tin nhập vào chưa hợp lệ",
                    'code' => 433
                ]
            );
        }

        $user = DB::table('users')->where('email', $email)->first();
        if (!$user) return response()->json(
            [
                'status' => 'error',
                'message'=>"Người dùng chưa đăng ký sử dụng hệ thống",
                'code' => 433
            ],
        );
        
        $userId = $user->id;
        $member = new Member();
        $member->projectId = $projectId;
        $member->userId = $userId;
        $project = Project::find($projectId);
        if ($ownerId === $project->getOwnerId()) {
            $count = DB::table('members')->where('projectId',$projectId)->where('userId', $userId)->count();
            if ($count) return response()->json([
                'success' => 'fails',
                'message' => 'Thành viên đã được thêm trước đó',
                'code' => 434
            ]);
            if ($project->members()->save($member)) {
                $data = [
                    'email' => $email,
                    'username' => $user->username,
                    'role' => 'Thành viên',
                    'id' => $user->id
                ];
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'message' => 'Thêm thành công thành viên vào dự án',
                    'code' => 200
                ]);
            }
                
            else
                return response()->json([
                    'success' => false,
                    'code' => 500,
                    'message' => 'Không thể thêm thành viên'
                ]);
        }
        else return response()->json([
            'success' => false,
            'message' => 'Bạn không phải chủ sở hữu project',
            'code' => 500
        ]);

    
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
    public function destroy($projectId, $id, Request $request)
    {   
        $user = UserController::user($request);
        $project = Project::find($projectId);
        if ($project->ownerId !== $user->id) 
        return response()->json([
            'status' => 'fails',
            'message' => 'Bạn không thể xóa do không phải là chủ sở hữu',
            'code' => 422
        ]);
        $member = DB::table('members')->where('userId' , $id)->where('projectId', $projectId);
        if ( $id == $this->ownerUser->id) {
            return response()->json([
                'status' => 'fails',
                'message' => 'Bạn không thể tự xóa mình ra khỏi dự án của bạn',
                'code' => 422
            ]);
        }
        
        if (!$member) return response()->json([
            'status' => 'fails',
            'message' => 'Xóa thành viên thất bại.',
            'code' => 400
        ]);

        $member->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Xóa thành viên thành công.',
            'code' => 200
        ]);
    }

    public function getMemberInfo() {

    }

}

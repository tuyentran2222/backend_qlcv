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
use App\Repositories\Member\MemberInterface;
use App\Repositories\Project\ProjectInterface;
use App\Repositories\User\UserInterface;

class MemberController extends Controller
{
    protected $project;
    protected $user;
    protected UserInterface $userInterface;
    protected MemberInterface $memberInterface;
    protected ProjectInterface $projectInterface;
    public function __construct(UserInterface $userRepository, MemberInterface $memberRepository, ProjectInterface $projectRepository)
    {
        $this->user = JWTAuth::parseToken()->authenticate();
        $this->userInterface = $userRepository;
        $this->memberInterface = $memberRepository;
        $this->projectInterface = $projectRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $this->user->getId();
        $projectId = isset($request->project) ? $request->project : '';
        if (!is_numeric($projectId) || $projectId === null) 
            return response()->json([
                'status' =>'error',
                'description' => 'Id của project là một số hoặc không được để trống.',
                'code' => 400
            ]);
        $check = false;
        $project = $this->projectInterface->find($projectId);
        if (!$project) return response()->json([
            'code' => 404,
            'message' => "Project không tồn tại"
        ]);
        // $ownerId = DB::table('projects')->where('id', $project->id)->select('ownerId')->first();
        $ownerId = $project->ownerId;
        $ownerProject = $this->userInterface->find($ownerId);
        $memberArray = array();
        if ($ownerId->ownerId === $ownerProject->id) $check=true;
        $member = $project->members()->get();
        $index = 0;
        $arrayRole = ["Owner", "Member" , "Developer", "Maintenance" , "Customer Support", "BA", "Leader", "Project Management"];
        foreach ($member as $m ) {
            $memberArray[$index] = DB::table('users')->select('email','username','id') -> where('id', $m->userId)->first();
            $memberArray[$index]->index = $index + 1;
            $memberArray[$index]->role = $arrayRole[$m->role];
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
        $role = $request->role;
        $ownerId = $this->user->getId();
        $projectId = isset($request->project) ? $request->project : '';

        $validator = Validator::make([
            'projectId' => $projectId,
            'email' => $email,
            'role' => $role
        ],
        [
            'projectId' => 'required|integer',
            'email' => 'required|email',
            'role' => 'integer'
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

        $user = $this->userInterface->getUserByEmail($email);
        if (!$user) return response()->json(
            [
                'status' => 'error',
                'message'=>"Người dùng chưa đăng ký sử dụng hệ thống",
                'code' => 433
            ],
        );
        $memberArray = [
            'projectId' => $projectId,
            'userId' => $user->id,
            'role' => $role,
        ];
        
        $userId = $user->id;
   
        $project = $this->projectInterface->find($projectId);
        $arrayRole = ["Owner", "Member" , "Developer", "Maintenance" , "Customer Support", "BA", "Leader", "Project Management"];
        if ($ownerId === $project->getOwnerId()) {
            $count = DB::table('members')->where('projectId',$projectId)->where('userId', $userId)->count();
            if ($count) return response()->json([
                'success' => 'fails',
                'message' => 'Thành viên đã được thêm trước đó',
                'code' => 434
            ]);
            $member = $this->memberInterface->create($memberArray);
            if ($member) {
                $data = [
                    'email' => $email,
                    'username' => $user->username,
                    'role' => $arrayRole[$role],
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($projectId, $id, Request $request)
    {   
        $project = $this->projectInterface->find($projectId);
        if ($project->ownerId !== $this->user->id) 
            return response()->json([
                'status' => 'fails',
                'message' => 'Bạn không thể xóa do không phải là chủ sở hữu',
                'code' => 422
            ]);
        if ( $id == $this->user->id) {
            return response()->json([
                'status' => 'fails',
                'message' => 'Bạn không thể tự xóa mình ra khỏi dự án của bạn',
                'code' => 422
            ]);
        }

        $member = $this->memberInterface->findMember($id, $projectId);
        if (!$member) return response()->json([
            'status' => 'fails',
            'message' => 'Xóa thành viên thất bại.',
            'code' => 400
        ]);

        $this->memberInterface->deleteMember($id, $projectId);
        return response()->json([
            'status' => 'success',
            'message' => 'Xóa thành viên thành công.',
            'code' => 200
        ]);
    }

    public function getMemberInfo(Request $request) {
        $userId = $this->user->getId();
        $projectId = isset($request->project) ? $request->project : '';

        if (!is_numeric($projectId) || $projectId === null) 
            return response()->json([
                'status' =>'error',
                'description' => 'Id của dự án là một số hoặc không được để trống.',
                'code' => 400
            ]);
        $check = false;

        $project = $this->projectInterface->find($projectId);
        if (!$project) return response()->json([
            'code' => 404,
            'message' => "Dự án không tồn tại"
        ]);

        $ownerId = $project->ownerId;
        $ownerProject = $this->userInterface->find($ownerId);
        $memberArray = array();
        
        $members = $this->memberInterface->getAllMemberOfProject($project);
        $index = 0;
        $arrayRole = ["Owner", "Member" , "Developer", "Maintainace" , "Customer Support", "BA", "Leader", "Project Management"];
        foreach ($members as $m ) {
            $u = $this->userInterface->find($m->userId);
            $memberArray[$index]['id'] = $u->id;
            $memberArray[$index]['name'] = $u->username;
            $index++;
        }
        return response()->json(
            [
                'code' => 200,
                'data' => $memberArray
            ]
        );
    }

}

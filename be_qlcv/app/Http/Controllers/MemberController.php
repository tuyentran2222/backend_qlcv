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
use App\Helpers\Helper;
class MemberController extends Controller
{
    protected $project;
    protected $user;
    protected UserInterface $userInterface;
    protected MemberInterface $memberInterface;
    protected ProjectInterface $projectInterface;
    const ROLE_ARRAY = ["Owner", "Member" , "Developer", "Maintenance" , "Customer Support", "BA", "Leader", "Project Management"];
    public function __construct(UserInterface $userRepository, MemberInterface $memberRepository, ProjectInterface $projectRepository)
    {
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
        $user = Helper::getUser();
        //can update
        $action = "get members of project";
        $userId = $user->id;
        $projectId = isset($request->project) ? $request->project : '';

        if (!is_numeric($projectId) || $projectId === null) 
            return Helper::getResponseJson(400, 'Id của project là một số hoặc không được để trống.', [], $action);
        $check = false;
        $project = $this->projectInterface->find($projectId);
        if (!$project) 
            return Helper::getResponseJson(404, 'Dự án không tồn tại.', [], $action);
        
        $ownerId = $project->ownerId;
        $ownerProject = $this->userInterface->find($ownerId);
        $memberArray = array();
        if ($ownerId->ownerId === $ownerProject->id) $check=true;
        $member = $project->members()->get();
        $index = 0;
        
        foreach ($member as $m ) {
            $memberArray[$index] = DB::table('users')->select('email','username','id') -> where('id', $m->userId)->first();
            $memberArray[$index]->index = $index + 1;
            $memberArray[$index]->role = MemberController::ROLE_ARRAY[$m->role];
            $memberArray[$index]->projectId = $projectId;
            $index ++;
            if ($m->userId === $userId) $check = true;
        }

        $dataReturn = [
            'projectId' => $projectId,
            'data' => $memberArray
        ];

        if ($check) return Helper::getResponseJson(200, 'Thành công.', $dataReturn, $action);
        return Helper::getResponseJson(400, 'Bạn không tham gia vào project', [], $action);
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
        $user = Helper::getUser();
        $action = "add member";
        $email = $request->email;
        $role = $request->role;
        $ownerId = $user->id;
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
            return Helper::getResponseJson(433, "Thông tin nhập vào chưa hợp lệ", [], $action, $validator->errors());
        }

        $user = $this->userInterface->getUserByEmail($email);
        if (!$user) return Helper::getResponseJson(433, "Người dùng chưa đăng ký sử dụng hệ thống", [], $action);

        $memberArray = [
            'projectId' => $projectId,
            'userId' => $user->id,
            'role' => $role,
        ];
        
        $userId = $user->id;
   
        $project = $this->projectInterface->find($projectId);

        if ($ownerId === $project->getOwnerId()) {
            $count = DB::table('members')->where('projectId',$projectId)->where('userId', $userId)->count();
            if ($count) return Helper::getResponseJson(434, 'Thành viên đã được thêm trước đó', [], $action); 
            $member = $this->memberInterface->create($memberArray);
            if ($member) {
                $dataReturn = [
                    'email' => $email,
                    'username' => $user->username,
                    'role' => MemberController::ROLE_ARRAY[$role],
                    'id' => $user->id
                ];
                return Helper::getResponseJson(200, 'Thêm thành công thành viên vào dự án', $dataReturn, $action);
            }
                
            else return Helper::getResponseJson(500, 'Không thể thêm thành viên', [], $action);
        }
        else return Helper::getResponseJson(500, 'Bạn không phải chủ sở hữu project', [], $action); 

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($projectId, $id, Request $request)
    {   
        $user = Helper::getUser();
        $action = "delete member";
        $project = $this->projectInterface->find($projectId);
        if ($project->ownerId !== $user->id) 
            return Helper::getResponseJson(422, 'Bạn không thể xóa do không phải là chủ sở hữu', [], $action);
            
        if ( $id == $user->id) {
            return Helper::getResponseJson(422, 'Bạn không thể tự xóa mình ra khỏi dự án của bạn', [], $action);
        }

        $member = $this->memberInterface->findMember($id, $projectId);
        if (!$member) return Helper::getResponseJson(400, 'Xóa thành viên thất bại.', [], $action);

        $this->memberInterface->deleteMember($id, $projectId);
        return Helper::getResponseJson(200, 'Xóa thành viên thành công.', [], $action);

    }

    public function getMemberInfo(Request $request) {
        $action = "get member information";
        $projectId = isset($request->project) ? $request->project : '';

        if (!is_numeric($projectId) || $projectId === null)
            return Helper::getResponseJson(400, 'Id của dự án là một số hoặc không được để trống.', [], $action);
      
        $project = $this->projectInterface->find($projectId);
        if (!$project)
            return Helper::getResponseJson(404, "Dự án không tồn tại", [], $action);
  
        $ownerId = $project->ownerId;
        $ownerProject = $this->userInterface->find($ownerId);
        $memberArray = array();
        
        $members = $this->memberInterface->getAllMemberOfProject($project);
        $index = 0;
        
        foreach ($members as $m ) {
            $u = $this->userInterface->find($m->userId);
            $memberArray[$index]['id'] = $u->id;
            $memberArray[$index]['name'] = $u->username;
            $index++;
        }
        return Helper::getResponseJson(200, "Thành công", $memberArray, $action);

    }

}

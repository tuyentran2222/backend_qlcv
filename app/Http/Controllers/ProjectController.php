<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Project;
use App\Repositories\Member\MemberInterface;
use App\Repositories\Project\ProjectInterface;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
class ProjectController extends Controller
{
    protected $user;
    protected ProjectInterface $projectInterface;
    protected MemberInterface $memberInterface;

    public function __construct(ProjectInterface $projectRepository, MemberInterface $memberRepository)
    {
        $this->projectInterface = $projectRepository;
        $this->memberInterface = $memberRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Helper::getUser();
        $action = "get all projects";

        //get all projects by user id
        $projects = $this->projectInterface->getAllProjectsByUser($user->id);
        $projectArray = array();
        $index = 0;

        if (!empty($projects)) {
            foreach($projects as  $project) {
                $projectArray[$index] = $project;
                $projectArray[$index]->index = $index;
                $projectArray[$index]->role = MemberController::ROLE_ARRAY[$project->role];
                $index++;
            }
        }
        return Helper::getResponseJson(200, "Lấy danh sách dự án thành công.", $projectArray, $action);
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
        $action = "create project";
        $user = Helper::getUser();

        $validator = Validator::make($request->all(), $this->getProjectRulesValidation());

        if ($validator->fails()) {
            return Helper::getResponseJson(422, "Thông tin nhập vào chưa hợp lệ", [], $action, $validator->errors());
        }

        $projectArray = [
            'projectCode' => $request->projectCode,
            'projectName' => $request->projectName,
            'projectStart' => $request->projectStart,
            'projectEnd'=> $request->projectEnd,
            'partner' => $request->partner,
            'status' => $request->status,
            'ownerId' => $user->getId()
        ];
 
        $project = $this->projectInterface->create($projectArray);

        if ($project) {
            $memberArray = [
                'userId' => $user->getId(),
                'role' => 0,
                'projectId' => $project->id
            ];
            $this->memberInterface->create($memberArray);
            return Helper::getResponseJson(200, "Thêm dự án thành công", $project, $action);
        }
        else return Helper::getResponseJson(500, "Thêm dự án thất bại", [], $action);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project, $id)
    {
        $user = Helper::getUser();
        $action = "show project";
        $project = $user->projects()->find($id);
    
        if (!$project) 
            return Helper::getResponseJson(400, "Dự án không tồn tại", [], $action);
        return Helper::getResponseJson(200, "Thành công", $project, $action);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function edit(int $id, Request $request)
    {
        $action = "edit project";
        $project = $this->projectInterface->find($id);
        if ($project) {
            return Helper::getResponseJson(200, "Thành công", $project, $action);
        }
        else {
            return Helper::getResponseJson(404, "Không tìm thấy dự án!", [], $action);
        }
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Project $project)
    { 
        //Validate data
        $action ="update project";
        $data = $request->only('projectCode', 'projectName', 'projectStart', 'projectEnd', 'partner', 'status');
        $validator = Validator::make($request->all(), $this->getProjectRulesValidation());
        if ($validator->fails()) {
            return Helper::getResponseJson(400, "Thông tin nhập vào chưa hợp lệ", [], $action, $validator->errors());
        }

        $check = false;
        try{
            $check = $this->authorize('update', $project);
        }
        catch(\Illuminate\Auth\Access\AuthorizationException $e) {
            
        }
  
        if (!$check) {
            return Helper::getResponseJson(401, "Không có quyền update dự án", [], $action);
        }

        $projectArray = [
            'projectCode' => $request->projectCode,
            'projectName' => $request->projectName,
            'projectStart' => $request->projectStart,
            'projectEnd' => $request->projectEnd,
            'partner' => $request->partner,
            'status' => $request->status
        ];

        //Request is valid, update project
        $project = $this->projectInterface->update($project->id, $projectArray);

        //project updated, return success response
        return Helper::getResponseJson(200, 'Dự án cập nhật thành công', $project, $action);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $project)
    {   
        $action = "delete project";
        $user = Helper::getUser();
        $project= $this->projectInterface->find((int)($project));
        if (!$project)
        return Helper::getResponseJson(404, 'Xóa dự án thất bại do dự án không tồn tại.', [], $action);
    
        $check = false;
        try{
            $check = $this->authorize('delete', $project);
        }
        catch(\Illuminate\Auth\Access\AuthorizationException $e) {
            
        }

        if (!$check) {
            return Helper::getResponseJson(403, "Không có quyền xóa dự án", [], $action);
        }

        $this->projectInterface->delete($project->id);
        return Helper::getResponseJson(200, 'Xóa dự án thành công.', [], $action);
    }
    
    public function getCountProjects() {
        $action = "get number of projects";
        $countTasks = TaskController::getCountAssignedTask();
        $user = Helper::getUser();
        $count = $this->memberInterface->getCountProjectByUser($user->id);
        $countTasks['countProjects'] = $count;
        return Helper::getResponseJson(200, 'Thành công.', $countTasks, $action);
    }

    public function getProjectRulesValidation($type = "create") {
        return [
            'projectCode' => 'required|string',
            'projectName' => 'required|string',
            'projectStart' => 'required|date',
            'projectEnd' => 'date',
            'partner' => 'string|required',
            'status' => 'integer|required',
        ];
    }

    public function getNumberProjectsByStatus() {
        $data = $this->projectInterface->getNumberProjectsByStatus();
        $status = [0,0,0,0];
        $count = 0;
        foreach ($data as $e) {
            if ($e->ownerId == Helper::getUser()->id) $count++;
            switch ($e->status) {
                case 1 : $status[0] = $status[0]+1;break;
                case 2 : $status[1] = $status[1]+1;break;
                case 3 : $status[2] = $status[2]+1;break;
                case 4 : $status[3] = $status[3]+1;break;
                default: break;
            }
            
        }
        
        return Helper::getResponseJson(200, "Thành công", [$status, [$count, count($data)-$count]], "status");
    }

}

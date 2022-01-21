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
        $this->user = JWTAuth::parseToken()->authenticate();
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
        $action = "get all projects";
        //get all projects by user id
        $projects = $this->projectInterface->getAllProjectsByUser($this->user->id);
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
            'ownerId' => $this->user->getId()
        ];
 
        $project = $this->projectInterface->create($projectArray);

        if ($project) {
            $memberArray = [
                'userId' => $this->user->getId(),
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
        $action = "show project";
        $project = $this->user->projects()->find($id);
    
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
        $project= $this->projectInterface->find((int)($project));
        if (!$project) return response()->json([
            'status' => 'fails',
            'code' => 401,
            'message' => 'Xóa project thất bại do project không tồn tại.'
        ]);

        if ($project->ownerId !== $this->user->id) 
            return Helper::getResponseJson(401, 'Xóa project thất bại do bạn không phải chủ sở hữu project.', [], $action);

        $this->projectInterface->delete($project->id);
        return Helper::getResponseJson(200, 'Xóa project thành công.', [], $action);
    }
    
    public function getCountProjects() {
        $action = "get number of projects";
        $count = $this->memberInterface->getCountProjectByUser($this->user->id);
        return Helper::getResponseJson(200, 'Thành công.', $count, $action);
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
}
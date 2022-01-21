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
        //get all projects by user id
        $projects = $this->projectInterface->getAllProjectsByUser($this->user->id);

        $projectArray = array();
        $index = 0;
        $arrayRole = ["Owner", "Member" , "Developer", "Maintainace" , "Customer Support", "BA", "Leader", "Project Management"];
        if (!empty($projects)) {
            foreach($projects as  $project) {
                $projectArray[$index] = $project;
                $projectArray[$index]->index = $index;
                $projectArray[$index]->role = $arrayRole[$project->role];
                $index++;
            }
            
        }

        return response()->json([
            [
                'code' => 200,
                'message' => "Trả danh sách dự án thành công.",
                'data' => $projectArray
            ]
        ]);

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
        $validator = Validator::make($request->all(), $this->getProjectRulesValidation());

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validator->errors(),
                    'message'=>"Thông tin nhập vào chưa hợp lệ",
                    'code' => 422
                ]
            );
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
            return response()->json([
                'status' => 'success',
                'code' => 200,
                "message" => "Thêm dự án thành công",
                'data' => $project
            ]);
        }
        else
            return response()->json([
                'status' => 'error',
                'code' => 500,
                "message" => "Thêm dự án thất bại",
            ]);
    
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project, $id)
    {
        $project = $this->user->projects()->find($id);
    
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, project not found.'
            ], 400);
        }
    
        return $project;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function edit(int $id, Request $request)
    {
        $project = $this->projectInterface->find($id);
        if ($project) {
            return response()->json([
                'status'=> 'success',
                'code' => 200,
                'project' => $project
            ]);
        }
        else {
            return response()->json([
                'status'=> 'error',
                'code' => 404,
                'message' => 'Không tìm thấy dự án!'
            ]);
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
        $data = $request->only('projectCode', 'projectName', 'projectStart', 'projectEnd', 'partner', 'status');
        $validator = Validator::make($request->all(), $this->getProjectRulesValidation());
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validator->errors(),
                    'description'=>"Thông tin nhập vào chưa hợp lệ",
                    'code' => 400
                ],
                400
            );
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
        return response()->json([
            'status' => 'success',
            'code' =>200,
            'message' => 'Dự án cập nhật thành công',
            'data' => $project
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $project)
    {   
        $project= $this->projectInterface->find((int)($project));

        if (!$project) return response()->json([
            'status' => 'fails',
            'code' => 401,
            'message' => 'Xóa project thất bại do project không tồn tại.'
        ]);

        if ($project->ownerId !== $this->user->id) 
            return response()->json([
                'status' => 'fails',
                'code' => 401,
                'message' => 'Xóa project thất bại do bạn không phải chủ sở hữu project.'
            ]);

        $this->projectInterface->delete($project->id);

        return response()->json([
            'status' => 'success',
             'code' => 200,
            'message' => 'Xóa project thành công.'
        ]);
    }
    
    public function getCountProjects() {
        $count = $this->memberInterface->getCountProjectByUser($this->user->id);
        return response()->json([
            'code' => 200,
            'data' => $count
        ]);
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

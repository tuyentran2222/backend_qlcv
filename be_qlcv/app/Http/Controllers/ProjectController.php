<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Project;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = JWTAuth::parseToken()->authenticate();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = UserController::user($request);
        if (!$user) return response()->json(
            [
                'status' => 'error',
                'message'=>"Bạn chưa đăng nhập vào hệt thống",
                'code' => 400
            ]
        );

        $projects = DB::table('members')
        ->join('projects', 'members.projectId' , '=' , 'projects.id')
        ->where('userId', $user->id)->orderBy('projects.created_at','desc')->get();

        $projectArray = array();
        $index = 0;
        if (!empty($projects)) {
            foreach($projects as  $project) {
                $projectArray[$index] = $project;
                $projectArray[$index]->index = $index;
                if ($project->userId === $project->ownerId) $projectArray[$index]->role = "Chủ sở hữu";
                else $projectArray[$index]->role = "Thành viên";
                $index++;
            }
            
        }
        return $projectArray;
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
        $user = UserController::user($request);
        if (!$user) return response()->json(
            [
                'status' => 'error',
                'message'=>"Bạn chưa đăng nhập vào hệt thống",
                'code' => 400
            ]
        );
        
        $validator = Validator::make($request->all(),
        [
            'projectCode' => 'required|string',
            'projectName' => 'required|string',
            'projectStart' => 'required|date',
            'projectEnd' => 'date',
            'partner' => 'string|required',
            'status' => 'integer|required',
        ]);

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
 
        $project = new Project();
        $project->projectCode = $request->projectCode;
        $project->projectName = $request->projectName;
        $project->projectStart=$request->projectStart;
        $project->projectEnd= $request->projectEnd;
        $project->partner= $request->partner;
        $project->status= $request->status;
        $project->ownerId = $this->user->getId();
        
        if ($this->user->projects()->save($project)) {
            $member = new Member();
            $member->userId = $this->user->getId();
            $member->projectId = $project->id;
            $member->save();
            return response()->json([
                'status' => 'success',
                'code' => 200,
                "message" => "Thêm dự án thành công",
                'data' => $project->toArray()
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
        $user = UserController::user($request);
        if (!$user) return response()->json(
            [
                'status' => 'error',
                'message'=>"Bạn chưa đăng nhập vào hệt thống",
                'code' => 400
            ]
        );
        $project = Project::find($id);
        // if ($project->ownerId !== $user->id) {
        //     return response()->json(
        //         [
        //             'status' => 'error',
        //             'message'=>"Bạn không thể sửa do không phải là chủ sở hữu",
        //             'code' => 400
        //         ]
        //     );
        // }
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
                'message' => 'No project ID Found'
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
        $validator = Validator::make($request->all(),
        [
            'projectCode' => 'required|string',
            'projectName' => 'required|string',
            'projectStart' => 'required|date',
            'projectEnd' => 'date',
            'partner' => 'string|required',
            'status' => 'integer|required',
        ]);
        
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
        $project = $project->update($projectArray);

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
        $project = Project::find($project);
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
        DB::table('members')->where('projectId', $project->id)->delete();
        $project->delete();
        return response()->json([
            'status' => 'success',
             'code' => 200,
            'message' => 'Xóa project thành công.'
        ]);
    }
    
    public function getCountProjects() {
        return response()->json([
            'code' => 200,
            'data' => Member::where('userId',$this->user->id)->get()->count()
        ]);
    }
}

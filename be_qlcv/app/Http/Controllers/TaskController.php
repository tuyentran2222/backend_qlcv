<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Executor;
use App\Models\Task;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Repositories\Member\MemberInterface;
use App\Repositories\Project\ProjectInterface;
use App\Repositories\Task\TaskInterface;

class TaskController extends Controller
{
    protected $project;
    protected $user;
    protected $userId;
    protected TaskInterface $taskInterface;
    protected ProjectInterface $projectInterface;
    protected MemberInterface $memberInterface;
    public function __construct(TaskInterface $taskRepository, ProjectInterface $projectRepository, MemberInterface $memberRepository)
    {
        $this->user = JWTAuth::parseToken()->authenticate();
        $this->taskInterface = $taskRepository;
        $this->projectInterface = $projectRepository;
        $this->memberInterface = $memberRepository;
        $this->userId = $this->user->id;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $id)
    {
        $parentTask = array();
        $tasks = $this->taskInterface->getAllTasksByProjectId($id);
        
        $checkMemberInProject = $this->memberInterface->checkMemberInProject($this->user->id, $id);
        if (!$checkMemberInProject) return response()->json(
            [
                'code' => 401,
                'message' => 'Bạn không thuộc project này'
            ]
        );
        foreach ($tasks as $index => $task) {
            $parentTask[$index] = $task;
            $projectName = $this->projectInterface->getBasicProjectInfo($task->projectId);
            ($parentTask[$index])->projectName = $projectName->projectName;
        }

        return response()->json([
            'data' => $parentTask,
            'count' => count($parentTask),
            'code' => 200,
            'message' => "Thành công"
        ]) ;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $parentId, $projectId)
    {
        $project = Project::find($projectId);
        $taskRules = $this->getTaskRulesValidation();
        $validator = Validator::make($request->all(), $taskRules);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validator->errors(),
                    'message' => "Thông tin nhập vào chưa hợp lệ",
                    'code' => 406
                ]
            );
        }
        
        $taskArray = [
            'taskCode' => $request->taskCode,
            'taskName' => $request->taskName,
            'taskDescription' => $request->taskDescription,
            'taskStart' => $request->taskStart,
            'taskEnd' => $request->taskEnd,
            'status' => (int)$request->status,
            'priority' => (int) $request->priority ,
            'levelCompletion' => $request->levelCompletion,
            'taskPersonId' => $request->taskPersonIds,
            'projectId' => $projectId,
            'ownerId' => $this->user->id,
            'parentId' => ($parentId) ? $parentId : null,
        ];
        
        // if (!$u)  return response()->json([
        //     'status' => 'fails',
        //     'code' => 404,
        //     'message' => 'Project chưa có thành viên đó hoặc project chưa tồn tại'
        // ]);
        $t = $this->taskInterface->find($parentId);
        if ($t) $taskArray["parentList"] = $t->parentList ? $t->parentList . ',' . $parentId : $parentId ;
        else $taskArray["parentList"] = "";
        $task = $this->taskInterface->create($taskArray);
        if ($task) {
            $executors = explode("," , $request->taskPersonIds);
            foreach ($executors as $executor) {
                $ex = new Executor();
                $ex->taskId = $task->id;
                $ex->userId = $executor;
                $ex->save();
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $task->toArray(),
                'message' =>'Thêm thành công công việc'
            ]);
        }
        else
            return response()->json([
                'status' => 'fail',
                'code' => 500,
                'message' => 'Không thể thêm công việc'
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
        $task = $this->taskInterface->find($id);
        $checkMemberInProject = $this->memberInterface->checkMemberInProject($this->user->id, $id);
        if (!$checkMemberInProject) return response()->json(
            [
                'code' => 401,
                'message' => 'Bạn không thuộc project này'
            ]
        );

        if (!$task) {
            return response()->json([
                'code' => 404,
                'success' => false,
                'message' => 'Công việc không tồn tại'
            ]);
        }
    
        return response()->json([
                'data' => $task,
                'code' => 200,
                'status' => 'success'
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $task = $this->taskInterface->find($id);
        $checkMemberInProject = $this->memberInterface->checkMemberInProject($this->user->id, $task->projectId);
        if (!$checkMemberInProject) return response()->json(
            [
                'code' => 401,
                'message' => 'Bạn không thuộc project này'
            ]
        );
        if (!$task) {
            return response()->json([
                'code' => 404,
                'success' => false,
                'message' => 'Công việc không tồn tại'
            ]);
        }
        
        //get comments of task
        $comments = $task->comments()->orderBy('created_at', 'desc')->get();
        $executors = $task->executors()->get();
        $executorsArrayIds = [];
        foreach ($executors as $executor) {
            $executorsArrayIds[] = (string)$executor["userId"];
        }
        $arrayComments = [];
        foreach ($comments as $index => $comment) {
            $user = $comment->user()->get()->first();
            $arrayComments[$index]['message'] = $comment->content;
            $arrayComments[$index]['time'] = $comment->updated_at;
            $arrayComments[$index]['name'] = $user->username;
            $arrayComments[$index]['avatar'] = $user->avatar;
            $arrayComments[$index]['id'] = $user->id;
            $arrayComments[$index]['comment_id'] = $comment->id;
        }

        //get child task
        $childTasks = $this->taskInterface->getChildTasks($task->id);
        return response()->json([
                'data' => $task,
                'comments' => $arrayComments,
                'childTasks'=> $childTasks,
                'executor' => $executorsArrayIds,
                'code' => 200,
                'status' => 'success'
            ]
        );
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
        $project = $this->taskInterface->find($id);
        $taskRules = $this->getTaskRulesValidation('update');
        $validator = Validator::make($request->all(), $taskRules);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validator->errors(),
                    'message'=>"Thông tin nhập vào chưa hợp lệ",
                    'code' => 406
                ]
            );
        }

        $taskArray = [
            'taskCode' => $request->taskCode,
            'taskName' => $request->taskName,
            'taskStart' => $request->taskStart,
            'taskEnd' => $request->taskEnd,
            'taskDescription' => $request->taskDescription,
            'status' => $request->status,
            'priority' => $request->priority,
            'levelCompletion' => $request->levelCompletion,
            'taskPersonId' => $request->taskPersonIds
        ];
        //Request is valid, update project
        $task = $this->taskInterface->update($id, $taskArray);
        //project updated, return success response
        return response()->json([
            'status' => 'success',
            'code' =>200,
            'message' => 'Dự án công việc thành công',
            'data' => $task
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = $this->taskInterface->find($id);
        if (!$task) return response()->json(
            [
                'code' => 404,
                'message' => "Không tìm thấy công việc tương ứng",
                'status' => 'fail'
            ]
        );

        $checkMemberInProject = $this->memberInterface->checkMemberInProject($this->user->id, $task->projectId);
        if (!$checkMemberInProject) return response()->json(
            [
                'code' => 401,
                'message' => 'Bạn không thuộc dự án này'
            ]
        );

        $task = $this->taskInterface->delete($id);
        return response()->json([
            'code'=> 200,
            'message'=>"Xóa công việc thành công"
        ]);
    }

    public function getAllComments(int $taskId) {
        $task = $this->taskInterface->find($taskId);
        $comments = $this->taskInterface->getAllCommentsOfTask($task);
        $arrayComments = [];
        foreach ($comments as $index => $comment) {
            $user = $comment->user()->get()->first();
            $arrayComments[$index]['message'] = $comment->content;
            $arrayComments[$index]['time'] = $comment->updated_at;
            $arrayComments[$index]['name'] = $user->username;
            $arrayComments[$index]['avatar'] = $user->avatar;
            $arrayComments[$index]['id'] = $user->id;
        }
        return response()->json([
            'code' => 200,
            'message' => "Thành công",
            'data' => $arrayComments,
        ]);
    }

    public function getAssignedTask() {

        $parentTask = array();
        $tasks = Task::where('taskPersonId',"like", "%".$this->userId."%")->orderBy('taskStart', 'desc')->get();
        $projects = array();
        foreach ($tasks as $index => $task) {
            $parentTask[$index] = $task;
            $projectName = Project::select('id', 'projectName')->where('id',$task->projectId)->get()->first();
            ($parentTask[$index])->projectName = $projectName->projectName;
        }

        return response()->json([
            'data' => $parentTask,
            'projects' => $projects,
            'count' => count($parentTask),
            'code' => 200,
            'message' => "Thành công"
        ]) ;
    }

    public function getCountAssignedTask() {
        $assignedTasksNumber = Task::where('taskPersonId', $this->userId)->orderBy('taskStart', 'desc')->get()->count();
        $createTasksNumber = Task::where('taskPersonId', $this->userId)->orderBy('taskStart', 'desc')->get()->count();
        return response()->json(
            [
                'countAssigned' => $assignedTasksNumber,
                'countCreate' => $assignedTasksNumber,
                'code' => 200,
                'message' => "Thành công"
            ]
        );
    }
    public function getCreatedTask() {
        $parentTask = array();
        $parentTaskId = array();  
        $tasks = Task::where('owner', $this->userId)->orderBy('taskStart', 'desc')->get();
        $projects = array();
        foreach ($tasks as $index => $task) {
            $parentTask[$index] = $task;
            $projectName = Project::select('id', 'projectName')->where('id',$task->projectId)->get()->first();
            ($parentTask[$index])->projectName = $projectName->projectName;
        }

        return response()->json([
            'data' => $parentTask,
            'projects' => $projects,
            'count' => count($parentTask),
            'code' => 200,
            'message' => "Thành công"
        ]) ;
    }

    public function getOvertimeTask() {
        $parentTask = array();
        $parentTaskId = array();
        $currentDate  = date('Y/m/d H:i:s');

        $tasks = Task::where('ownerId', $this->userId)->where('taskEnd', '<', $currentDate)->where('status', '<' , 3)->orderBy('taskStart', 'desc')->get();
        $projects = array();
        foreach ($tasks as $index => $task) {
            $parentTask[$index] = $task;
            $projectName = Project::select('id', 'projectName')->where('id',$task->projectId)->get()->first();
            ($parentTask[$index])->projectName = $projectName->projectName;
        }

        return response()->json([
            'data' => $parentTask,
            'projects' => $projects,
            'count' => count($parentTask),
            'code' => 200,
            'message' => "Thành công"
        ]) ;
    }

    /**
     * get rules validation of task
     */
    public function getTaskRulesValidation($type = 'create') {
        return
        [
            'taskCode' => ($type==='create') ? 'required|string|unique:tasks' : 'required|string',
            'taskName' => 'required|string',
            'taskDescription' => 'required|string',
            'taskStart' => 'required|date',
            'taskEnd' => 'date|nullable',
            'status' => 'required',
            'priority' => 'required',
            'levelCompletion' => 'integer',
            'parentId' => 'integer|nullable'
        ];
    }

}

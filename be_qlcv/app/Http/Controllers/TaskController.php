<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Executor;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Project;
use App\Repositories\Member\MemberInterface;
use App\Repositories\Project\ProjectInterface;
use App\Repositories\Task\TaskInterface;
use App\Helpers\Helper;
use Illuminate\Auth\Access\AuthorizationException;

class TaskController extends Controller
{
    protected $project;
    protected $user;

    protected TaskInterface $taskInterface;
    protected ProjectInterface $projectInterface;
    protected MemberInterface $memberInterface;
    
    public function __construct(TaskInterface $taskRepository, ProjectInterface $projectRepository, MemberInterface $memberRepository)
    {
        $this->taskInterface = $taskRepository;
        $this->projectInterface = $projectRepository;
        $this->memberInterface = $memberRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $id)
    {
        //can update
        $action = "get list of task";
        $user = Helper::getUser();
        $parentTask = array();
        $tasks = $this->taskInterface->getAllTasksByProjectId($id);

        $checkMemberInProject = $this->memberInterface->checkMemberInProject($user->id, $id);
        if (!$checkMemberInProject)
            return Helper::getResponseJson(401, 'Không có quyền xem dự án vì không thuộc dự án', [], $action);
 
        foreach ($tasks as $index => $task) {
            $parentTask[$index] = $task;
            $projectName = $this->projectInterface->getBasicProjectInfo($task->projectId);
            ($parentTask[$index])->projectName = $projectName->projectName;
        }
        $dataReturn = [
            'data' => $parentTask,
            'count' => count($parentTask)
        ];

        return Helper::getResponseJson(200, 'Thành công', $dataReturn, $action);

        // return response()->json([
        //     'data' => $parentTask,
        //     'count' => count($parentTask),
        //     'code' => 200,
        //     'message' => "Thành công"
        // ]) ;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $parentId, $projectId)
    {
        $action = "create task";
        $user = Helper::getUser();
        $project = Project::find($projectId);
        $taskRules = $this->getTaskRulesValidation();
        $validator = Validator::make($request->all(), $taskRules);

        if ($validator->fails()) {
            return Helper::getResponseJson(406, "Thông tin nhập vào chưa hợp lệ", [], $action, $validator->errors());
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
            'ownerId' => $user->id,
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
            return Helper::getResponseJson(200, 'Thêm thành công công việc', $task->toArray(), $action);
        }
        else return Helper::getResponseJson(500, 'Không thể thêm công việc', [], $action);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $action = "show task";
        $task = $this->taskInterface->find($id);
        return Helper::getResponseJson(200, 'Thành công', $task, $action);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //can update
        $action = "edit task";
        $user = Helper::getUser();
        $task = $this->taskInterface->find($id);
        
        //get comments of task
        $comments = $task->comments()->orderBy('updated_at', 'desc')->get();
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
        $dataReturn = [
            'data' => $task,
            'comments' => $arrayComments,
            'childTasks'=> $childTasks,
            'executor' => $executorsArrayIds,
        ];
        return Helper::getResponseJson(200, 'Thành công', $dataReturn, $action);
        // return response()->json([
        //         'data' => $task,
        //         'comments' => $arrayComments,
        //         'childTasks'=> $childTasks,
        //         'executor' => $executorsArrayIds,
        //         'code' => 200,
        //         'status' => 'success'
        //     ]
        // );
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
        $action = "update task";
        $task = $this->taskInterface->find($id);
        $taskRules = $this->getTaskRulesValidation('update');
        $validator = Validator::make($request->all(), $taskRules);

        //check validate of inputs
        if ($validator->fails()) {
            return Helper::getResponseJson(406, "Thông tin nhập vào chưa hợp lệ", [], $action, $validator->errors());
        }
        
        //check task policy
        $checkAuthorization = false;
        try {
            $checkAuthorization = $this->authorize('update',$task);
        }
        catch ( AuthorizationException $e) {

        }

        if (! $checkAuthorization) return Helper::getResponseJson(401, 'Bạn không có quyền sửa công việc', [], $action);
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
        //Request is valid, update task
        $task = $this->taskInterface->update($id, $taskArray);
        //task updated, return success response
        return Helper::getResponseJson(200, 'Dự án công việc thành công', $task, $action);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $action = "delete task";
        $task = $this->taskInterface->find($id);
        $checkAuthorization = false;
        try {
            $checkAuthorization = $this->authorize('update',$task);
        }
        catch ( AuthorizationException $e) {

        }
    
        if (! $checkAuthorization) return Helper::getResponseJson(401, 'Bạn không có quyền xóa công việc', [], $action);
        $task = $this->taskInterface->delete($id);
            return Helper::getResponseJson(200, "Xóa công việc thành công", [], $action);
    }

    public function getAllComments(int $id) {
        $action = "get all comments";
        $task = $this->taskInterface->find($id);
        $user = Helper::getUser(); 
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
        return Helper::getResponseJson(200, "Thành công",  $arrayComments, $action);
    }

    public function getAssignedTask() {
        // can update
        $userId = Helper::getUser()->id;
        $action = 'get assigned task';
        $parentTask = array();
        $tasks = Task::where('taskPersonId',"like", "%".$userId."%")->orderBy('taskStart', 'desc')->get();
        $projects = array();
        foreach ($tasks as $index => $task) {
            $parentTask[$index] = $task;
            $projectName = Project::select('id', 'projectName')->where('id',$task->projectId)->get()->first();
            ($parentTask[$index])->projectName = $projectName->projectName;
        }

        $dataReturn = [
            'data' => $parentTask,
            'projects' => $projects,
            'count' => count($parentTask)
        ];

        return Helper::getResponseJson(200, "Thành công",  $dataReturn, $action);

    }

    public function getCountAssignedTask() {
        // can update
        $action = 'get count task';
        $userId = Helper::getUser()->id;
        $assignedTasksNumber = Task::where('taskPersonId', $userId)->orderBy('taskStart', 'desc')->get()->count();
        $createTasksNumber = Task::where('taskPersonId', $userId)->orderBy('taskStart', 'desc')->get()->count();
        $dataReturn = [
            'countAssigned' => $assignedTasksNumber,
            'countCreate' => $assignedTasksNumber
        ];
        return Helper::getResponseJson(200, "Thành công",  $dataReturn, $action);
    }

    public function getCreatedTask() {
        //can update
        $action = "get created task";
        $userId = Helper::getUser()->id;
        $parentTask = array();
        $parentTaskId = array();  
        $tasks = Task::where('owner', $userId)->orderBy('taskStart', 'desc')->get();
        $projects = array();
        foreach ($tasks as $index => $task) {
            $parentTask[$index] = $task;
            $projectName = Project::select('id', 'projectName')->where('id',$task->projectId)->get()->first();
            ($parentTask[$index])->projectName = $projectName->projectName;
        }

        $dataReturn = [
            'data' => $parentTask,
            'projects' => $projects,
            'count' => count($parentTask)
        ];

        return Helper::getResponseJson(200, "Thành công",  $dataReturn, $action);
    }

    public function getOvertimeTask() {
        //can update
        $action = "get task overtime";
        $parentTask = array();
        $parentTaskId = array();
        $currentDate  = date('Y/m/d H:i:s');
        $userId = Helper::getUser()->id;
        $tasks = Task::where('ownerId', $userId)->where('taskEnd', '<', $currentDate)->where('status', '<' , 3)->orderBy('taskStart', 'desc')->get();
        $projects = array();
        foreach ($tasks as $index => $task) {
            $parentTask[$index] = $task;
            $projectName = Project::select('id', 'projectName')->where('id',$task->projectId)->get()->first();
            ($parentTask[$index])->projectName = $projectName->projectName;
        }

        $dataReturn = [
            'data' => $parentTask,
            'projects' => $projects,
            'count' => count($parentTask)
        ];

        return Helper::getResponseJson(200, "Thành công",  $dataReturn, $action);
    }

    public function getExecutorsOfTask(Task $task) {
        $action ='get executors of task';
        $executors = $this->taskInterface->getExecutorsOfTask($task);
        $dataReturn = [];
        foreach ($executors as $index => $executor) {
            $user = $executor->user()->get()[0];
            $dataReturn[$index]['name'] = $user->username;
            $dataReturn[$index]['id'] = $user->id;
        }
        return Helper::getResponseJson(200, 'Thành công', $dataReturn, $action);
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

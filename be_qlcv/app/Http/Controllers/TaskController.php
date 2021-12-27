<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Project;

class TaskController extends Controller
{
    protected $project;
    protected $user;
    protected $userId;

    public function __construct()
    {
        $this->user = JWTAuth::parseToken()->authenticate();
        $this->userId = $this->user->id;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!$this->user) return response()->json(
            [
                'code' => 400,
                'message' => "Người dùng chưa đăng nhập vào hệ thống",
                'status' => 'fail'
            ]
        );
        
        $parentTask = array();
        $parentTaskId = array();
    
        $tasks = Task::where('taskPersonId', $this->userId)->orderBy('taskStart', 'desc')->get();
        $projects = array();

        foreach ($tasks as $index => $task) {
            if (!empty($task->parentList)) {
                $array = explode(',' , $task->parentList );
                $t = DB::table('tasks')->where('id', $array[0])->first();
                if (in_array($array[0],$parentTaskId)) continue;
                $parentTaskId[] = $array[0];
                $parentTask[$index] = DB::table('tasks')->where('id', $array[0])->first();
            }
            else $parentTask[$index] = $task;
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
    public function store(Request $request, $parentId, $projectId)
    {
        $project = Project::find($projectId);
        $validator = Validator::make($request->all(),
        [
            'taskCode' => 'required|string|unique:tasks',
            'taskName' => 'required|string',
            'taskDescription' => 'required|string',
            'taskStart' => 'required|date',
            'taskEnd' => 'date|nullable',
            'status' => 'integer|required',
            'priority' => 'integer|required',
            'levelCompletion' => 'integer',
            'taskPersonId' => 'integer|required',
            'parentId' => 'integer|nullable',
            // 'parentList' => 'String|nullable'
        ]);

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
        
        $task = new Task();
        $task->taskCode = $request->taskCode;
        $task->taskName = $request->taskName;
        $task->taskDescription = $request->taskDescription;
        $task->taskStart = $request->taskStart;
        $task->taskEnd = $request->taskEnd;
        $task->status = $request->status;
        $task->priority = $request->priority;
        $task->levelCompletion = $request->levelCompletion;
        $task->taskPersonId = $request->taskPersonId;
        $task->projectId = $projectId;
        if ($parentId) $task->parentId = $parentId;

        $u = DB::table('members')->where('userId',$request->taskPersonId)->where('projectId',$projectId)->first();

        if (!$u)  return response()->json([
            'status' => 'fails',
            'code' => 404,
            'message' => 'Project chưa có thành viên đó hoặc project chưa tồn tại'
        ]);

        $t = DB::table('tasks')->where('id',$parentId)->first();
        if ($t) $task->parentList = $t->parentList ? $t->parentList.','.$parentId : $parentId ;
        else $task->parentList = '';
        if ($project->tasks()->save($task))
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $task->toArray(),
                'message' =>'Thêm thành công công việc'
            ]);
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
        $task = Task::find($id);
        $projectId = $task->projectId;
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, project not found.'
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
        $task = Task::find($id);
        $projectId = $task->projectId;
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, project not found.'
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = Task::find($id);
        if (!$task) return response()->json(
            [
                'code' => 404,
                'message' => "Không tìm thấy công việc tương ứng",
                'status' => 'fail'
            ]
        );

        DB::table('tasks')->where('parent');
    }

    public function getAllComments(int $taskId) {
        $task = Task::find($taskId);
        $comments = $task->comments()->orderBy('created_at', 'desc')->get();
        $arrayUsers = [];
        $arrayComments = [];
        foreach ($comments as $index => $comment) {
            $user = $comment->user()->get()->first();
            $arrayComments[$index]['message'] = $comment->content;
            $arrayComments[$index]['time'] = $comment->updated_at;
            $arrayComments[$index]['name'] = $user->username;
            $arrayComments[$index]['avatar'] = $user->avatar;
        }
        return response()->json([
            'code' => 200,
            'message' => "Thành công",
            'data' => $arrayComments,
        ]);
    }

}

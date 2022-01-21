<?php

namespace App\Repositories\Task;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Repositories\EloquentRepository;
use App\Repositories\Task\TaskInterface;
use Illuminate\Support\Facades\DB;

class TaskRepository extends EloquentRepository implements TaskInterface
{
    public function model(): string
    {
        return Task::class;
    }

    public function getModel()
    {
        return Task::class;
    }

    public function index()
    {
        return $this->model->all();
    }

    public function getAllTasksByProjectId($id)
    {
        return $this->model->where('projectId', $id)
        ->where('parentList', null)->orWhere('parentList', "")
        ->where('projectId', $id)
        ->orderBy('taskStart', 'desc')
        ->get();
    }

    public function getChildTasks($parentTaskId) {
        return $this->model->where('parentId', $parentTaskId)->get();
    }

    public function getAllCommentsOfTask(Task $task) {
        return $task->comments()->orderBy('created_at', 'desc')->get();
    }

}
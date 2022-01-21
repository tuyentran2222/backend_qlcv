<?php

namespace App\Repositories\Task;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

interface TaskInterface
{
    public function getAllTasksByProjectId($id);

    public function create(array $attributes);
    
    public function find(int $id);

    public function update($id, array $attributes);

    public function delete($id);

    public function getChildTasks($parentTaskId);

    public function getAllCommentsOfTask(Task $task);

}
<?php

namespace App\Repositories\Project;

use App\Helpers\Helper;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Repositories\EloquentRepository;
use App\Repositories\Project\ProjectInterface;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\Help;

class ProjectRepository extends EloquentRepository implements ProjectInterface
{
    public function model(): string
    {
        return Project::class;
    }

    public function getModel()
    {
        return Project::class;
    }

    public function index()
    {
        return $this->model->all();
    }

    public function getALlProjectsByUser($id) {
       return DB::table('members')
        ->join('projects', 'members.projectId' , '=' , 'projects.id')
        ->where('userId', $id)->orderBy('projects.created_at','desc')->get();
    }

    public function getBasicProjectInfo($id) {
        return $this->model()::select('id', 'projectName')->where('id', $id)->get()->first();
    }

    public function getNumberProjectsByStatus()
    {
        return $this->model()::join('members', 'projects.id', '=','members.projectId')
        ->where('members.userId', '=', Helper::getUser()->id )
        ->get(['projects.id', 'projects.status', 'projects.ownerId']);
    }

}
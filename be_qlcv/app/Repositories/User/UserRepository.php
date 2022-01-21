<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\EloquentRepository;
use App\Repositories\UserProfile\UserProfileInterface;
use Illuminate\Support\Facades\DB;

class UserRepository extends EloquentRepository implements UserInterface
{
    public function model(): string
    {
        return User::class;
    }


    public function getModel()
    {
        return User::class;
    }

    
    public function index()
    {
        return $this->model->all();
    }

    /**
     * get all projects to which a person belongs
     */
    public function getAllProjects($userId, Request $request)
    {
        return DB::table('members')->join('projects', 'members.projectId', "=", 'projects.id')->where('userId', '=', $userId)->distinct('projectId')->get(['projectName', 'projectId']);
    }

    public function getUserByEmail($email){
        return $this->model->where('email', $email)->first();
    }

}
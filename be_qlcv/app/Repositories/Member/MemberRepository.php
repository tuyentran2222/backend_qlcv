<?php

namespace App\Repositories\Member;

use App\Models\Member;
use Illuminate\Http\Request;
use App\Repositories\EloquentRepository;
use App\Repositories\Member\MemberInterface;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Boolean;
use App\Models\Project;
class MemberRepository extends EloquentRepository implements MemberInterface
{
    public function model(): string
    {
        return Member::class;
    }

    public function getModel()
    {
        return Member::class;
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

    public function getCountProjectByUser($userId) {
        return $this->model->where('userId' , $userId)->get()->count();
    }

    public function checkMemberInProject($memberId, $projectId){
        return (Boolean) $this->model->where('projectId', $projectId)->where('userId', $memberId)->get()->count();
    }

    public function findMember($memberId, $projectId) {
        return $this->model->where('userId' , $memberId)->where('projectId', $projectId);
    }

    public function deleteMember($memberId, $projectId) {
        $member =  $this->model->where('userId' , $memberId)->where('projectId', $projectId);
        $member->delete();
    }

    public function getAllMemberOfProject(Project $project) {
        return $project->members()->get();
    }

}
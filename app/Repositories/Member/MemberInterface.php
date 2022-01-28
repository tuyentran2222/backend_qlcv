<?php

namespace App\Repositories\Member;

use Illuminate\Http\Request;
use App\Models\Project;
interface MemberInterface
{
    public function create(array $attributes);
    public function getCountProjectByUser($userId);
    public function checkMemberInProject($memberId, $projectId);
    public function findMember($memberId, $projectId);
    public function deleteMember($memberId, $projectId);
    public function getAllMemberOfProject(Project $project);
}
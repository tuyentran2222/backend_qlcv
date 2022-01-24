<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use Closure;
use Illuminate\Http\Request;
use App\Repositories\Member\MemberInterface;
use App\Repositories\Task\TaskInterface;

class RedirectIfNotInProject
{

    protected MemberInterface $memberInterface;
    protected TaskInterface $taskInterface;
    public function __construct(MemberInterface $memberRepository, TaskInterface $taskRepository) 
    {
        $this->memberInterface = $memberRepository;
        $this->taskInterface = $taskRepository;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $action = "middleware";
        $id = (int) $request->route()->parameter('id');
        $user = Helper::getUser();
        $task = $this->taskInterface->find($id);
        if (!$task) {
            return Helper::getResponseJson(404, 'Công việc không tồn tại', [], $action );
        }

        $checkMemberInProject = $this->memberInterface->checkMemberInProject($user->id, $task->projectId, $action);
        if (!$checkMemberInProject) 
            return Helper::getResponseJson(401, 'Bạn không thuộc dự án này', [], $action);
        return $next($request);
    }
}

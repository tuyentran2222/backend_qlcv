<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use Closure;
use Illuminate\Http\Request;
use App\Repositories\Member\MemberInterface;
use App\Repositories\Task\TaskInterface;

class IsAdmin
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
        $action = "middleware is admin";
        $user = Helper::getUser();
        if ($user->is_admin === 0)
        return Helper::getResponseJson(401, 'Bạn không phải là admin', [], $action);
        return $next($request);
    }
}

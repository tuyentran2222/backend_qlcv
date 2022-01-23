<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Repositories\Member\MemberInterface;

class TaskPolicy
{
    use HandlesAuthorization;

    protected MemberInterface $memberInterface;
    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct(MemberInterface $memberRepository)
    {
        $this->memberInterface = $memberRepository;
    }

    public function update(User $user, Task $task) {
       
        return $user->id === $task->ownerId || ($task->getOwnerIdOfProject() === $user->id);
    }

    public function delete(User $user, Task $task) {
        return $user->id === $task->ownerId || ($task->getOwnerIdOfProject() === $user->id);
    }
    
}

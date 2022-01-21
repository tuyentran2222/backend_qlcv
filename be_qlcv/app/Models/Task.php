<?php

namespace App\Models;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'taskCode',
        'taskName',
        'taskDescription',
        'taskStart',
        'taskEnd',
        'status',
        'priority',
        'levelCompletion',
        'taskPersonId',
        'parentId',
        'projectId',
        'parentList',
        'ownerId'
    ];

    public static function equal($task1, $task2) {
        return $task1->taskCode === $task2->taskCode;
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function totalComment(){
        return Comment::where('id', $this->id)->sum('value');
    }

    public function comments() {
        return $this->hasMany(Comment::class,'task_id','id');
    }

    public function executors() {
        return $this->hasMany(Executor::class,'taskId','id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Executor extends Model
{
    use HasFactory;
    protected $table = "executors";
    protected $fillable = [
        'id',
        'taskId',
        'userId'
    ];

    /**
     * Get the task that owns the comment
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'taskId');
    }

    public function user() {
        return $this->belongsTo(User::class, 'userId');
    }
}

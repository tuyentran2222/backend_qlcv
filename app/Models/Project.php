<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $tables = "projects";
    protected $fillable = [
        'projectCode', 'projectName', 'projectStart', 'projectEnd', 'partner','status', 'member', 'ownerId'
    ];
    public function members()
    {
        return $this->hasMany(Member::class,'projectId');
    }

    public function getOwnerId() {
        return $this->ownerId;
    }

    public function tasks() {
        return $this->hasMany(Task::class, 'projectId');
    }

}

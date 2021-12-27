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
}

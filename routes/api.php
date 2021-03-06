<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\MemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthController;
use App\Events\CommentEvent;
use App\Helpers\Helper;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::get('/test', function(){
    return 'Test successfully';
});
Route::post('/users/login', [AuthController::class, 'login']);
Route::post('/users/register', [AuthController::class, 'register']);
Route::post("/authentication/verifyToken", [AuthController::class, 'verifyToken']);
Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('getUser', [UserController::class, 'getUser']);
    Route::get('getAllProjects', [UserController::class, 'getAllProjects']);
    Route::post('user/update', [UserController::class, 'update']);
    Route::get('/users', [UserController::class, 'index'])->middleware('isAdmin');
    Route::get('projects/', [ProjectController::class, 'index']);
    Route::get('projects/{id}', [ProjectController::class, 'show']);
    Route::post('create', [ProjectController::class, 'store']);
    Route::patch('projects/update/{project}',  [ProjectController::class, 'update']);
    Route::get('projects/edit/{project}',  [ProjectController::class, 'edit']);
    Route::delete('projects/delete/{project}',  [ProjectController::class, 'destroy']);
    Route::get('status/projects/', [ProjectController::class, 'getNumberProjectsByStatus']);
    Route::get('/count/projects', [ProjectController::class, 'getCountProjects']);

    Route::get('members/{project}',  [MemberController::class, 'index']);
    Route::post('members/add/{project}/',  [MemberController::class, 'store']);
    Route::delete('members/{projectId}/delete/{id}',  [MemberController::class, 'destroy']);
    Route::get('members/memberInfo/{project}',  [MemberController::class, 'getMemberInfo']);

    Route::get('projects/{id}/tasks', [TaskController::class, 'index']);
    Route::get('tasks/assigned',  [TaskController::class, 'getAssignedTask']);

    Route::get('task/{id}/comments',  [TaskController::class, 'getAllComments'])->middleware('checkUserInProject');
    Route::post('tasks/create/{parentId}/projects/{projectId}',  [TaskController::class, 'store']);
    Route::get('tasks/edit/{id}',  [TaskController::class, 'edit'])->middleware('checkUserInProject');
    Route::delete('tasks/delete/{id}',  [TaskController::class, 'destroy'])->middleware('checkUserInProject');
    Route::post('tasks/update/{id}',  [TaskController::class, 'update'])->middleware('checkUserInProject');
    Route::get('tasks/getCount',  [TaskController::class, 'getCountAssignedTask']);
    Route::get('tasks/overtime',  [TaskController::class, 'getOvertimeTask']);
    Route::get('task/{task}/executors', [TaskController::class, 'getExecutorsOfTask']);

    //comment
    Route::post('tasks/{id}/comment/add',  [CommentController::class, 'store'])->middleware('checkUserInProject');
    Route::delete('comments/delete/{id}', [CommentController::class, 'destroy']);
    Route::post('comments/update/{id}', [CommentController::class, 'update']);

    // Route::get('users',  [TaskController::class, 'getAllComments'])->middleware('isAdmin');
    Route::delete('users/delete/{id}', [UserController::class, 'destroy'])->middleware('isAdmin');
    Route::post('admin/user/update/{id}', [UserController::class, 'adminUpdate'])->middleware('isAdmin');
});

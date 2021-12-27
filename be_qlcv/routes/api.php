<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\MemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
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

Route::post('/users/login', [UserController::class, 'login']);
Route::post('/users/register', [UserController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('logout', [UserController::class, 'logout']);
    Route::get('getUser', [UserController::class, 'getUser']);
    Route::get('getAllProjects', [UserController::class, 'getAllProjects']);
    Route::post('user/update/{id}', [UserController::class, 'update']);

    Route::get('projects', [ProjectController::class, 'index']);
    Route::get('projects/{id}', [ProjectController::class, 'show']);
    Route::post('create', [ProjectController::class, 'store']);
    Route::put('projects/update/{project}',  [ProjectController::class, 'update']);
    Route::get('projects/edit/{project}',  [ProjectController::class, 'edit']);
    Route::delete('projects/delete/{project}',  [ProjectController::class, 'destroy']);
    Route::get('/count/projects', [ProjectController::class, 'getCountProjects']);

    Route::get('members/{project}',  [MemberController::class, 'index']);
    Route::post('members/add/{project}/',  [MemberController::class, 'store']);
    
    Route::delete('members/{projectId}/delete/{id}',  [MemberController::class, 'destroy']);
    Route::get('tasks/',  [TaskController::class, 'index']);
    Route::get('task/{taskId}/comments',  [TaskController::class, 'getAllComments']);
    Route::post('tasks/create/{parentId}/projects/{projectId}',  [TaskController::class, 'store']);
    Route::get('tasks/edit/{taskId}',  [TaskController::class, 'edit']);
    Route::post('tasks/{taskId}/comment/add',  [CommentController::class, 'store']);
});

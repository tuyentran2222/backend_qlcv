<?php

namespace App\Repositories\User;

use Illuminate\Http\Request;

interface UserInterface
{
    public function find($id);
    public function delete($id);
    public function create(array $attributes);
    public function index();
    public function update($id, array $attributes);
    public function getAllProjects($userId,Request $request);
    public function getUserByEmail($email);
}
<?php

namespace App\Repositories\Comment;

use Illuminate\Http\Request;

interface CommentInterface
{
    public function create(array $attributes);
    public function find(int $id);
    public function update($id, array $attributes);
    public function delete($id);
}
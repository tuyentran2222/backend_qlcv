<?php

namespace App\Repositories\Comment;
use App\Models\Comment;
use App\Repositories\EloquentRepository;
use App\Repositories\Comment\CommentInterface;
use Illuminate\Support\Facades\DB;

class CommentRepository extends EloquentRepository implements CommentInterface
{
    public function model(): string
    {
        return Comment::class;
    }

    public function getModel()
    {
        return Comment::class;
    }

    public function index()
    {
        return $this->model->all();
    }
}
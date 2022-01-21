<?php

namespace App\Repositories\Authentication;

use Illuminate\Http\Request;

interface AuthInterface
{
    public function register(Request $request);
    public function getUserByToken($token);
}
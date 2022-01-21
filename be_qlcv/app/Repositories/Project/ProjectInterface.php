<?php

namespace App\Repositories\Project;

use Illuminate\Http\Request;

interface ProjectInterface
{
    /**
     * get all project by user id
     */
    public function getAllProjectsByUser(int $id);

    /**
     * create new project
     */
    public function create(array $attributes);
    
    /**
     * find project by id
     */
    public function find(int $id);

    /**
     * update project
     * @param $id int
     * @param $attributes array
     * @return mixed
     */
    public function update($id, array $attributes);

    /**
     * delete project by id
     */
    public function delete($id);

    /**
     * get basic information of project include id and name
     */
    public function getBasicProjectInfo($id);

}
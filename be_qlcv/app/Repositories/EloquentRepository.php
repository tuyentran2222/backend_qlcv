<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentRepository implements RepositoryInterface{

    /**
     * @var Model
     */
    protected $model;
    
    /**
     * 
     */
    public function __construct()
    {
        $this->setModel();
    }

    /**
     * get model
     */
    abstract public function getModel();

    /**
     * set model
     */
    public function setModel() {
        $this->model = app()->make($this->getModel());
    }

    /**
     * get all
     * @return Collection|static[]
     */
    public function getAll() {
        return $this->model->all();
    }

    /**
     * get item by id
     * @param $id
     * @return mixed
     */
    public function find($id) {
        $result = $this->model->find($id);

        return $result;
    }

    /**
     * create item
     * @param $attributes
     * @return mixed
     */
    public function create(array $attributes) {
        return $this->model->create($attributes);
    }

    /**
     * update item
     */
    public function update($id, array $attributes) {
        $result = $this->model->find($id);
        if ($result) {
            $result->update($attributes);
            return $result;
        }
        else return false;
    }

    /**
     * delete item
     */
    public function delete($id) {
        $result = $this->model->find($id) ;
        if ($result) {
            $result->delete();
            return true;
        }
        return false;
    }

} 

?>
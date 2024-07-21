<?php

namespace app\model;
use think\Model;

class VisitModel extends Model
{
    protected $name = 'visits';

    public function addVisit(array $data){
        return $this->save($data);
    }
}
<?php

namespace app\model;

use think\Model;

class ApiKeyModel extends Model
{
    protected $name = 'api_keys';
    protected $pk = 'id';

    public function addApiKey(array $data){
        return $this->save($data);
    }

    public function getAllApiKeys(){
        return $this->select();
    }

    public function deliteApiKey(int $id){
        return $this->where('id', $id)->delete();
    }

    public function existApikey(string $key){
        $apikey = $this->where('key', $key)->find();
        return !is_null($apikey);
    }
}
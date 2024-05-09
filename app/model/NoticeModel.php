<?php

namespace app\model;
use think\Model;
class NoticeModel extends Model
{
    protected $name = 'notice';
    protected $pk = 'id';

    public function getNoticeById(int $id)
    {
        return $this->find($id);
    }

    public function getAllNotice()
    {
        return $this->select();
    }

    public function addNotice(array $data)
    {
        return $this->save($data);
    }

    public function updateNotice(int $id, array $data)
    {
        return $this->where('id', $id)->update($data);
    }

    public function deleteNotice(int $id)
    {
        return $this->where('id', $id)->delete();
    }

    public function existNotice(int $id): bool
    {
        $notice = $this->where('id', $id)->find();
        return !is_null($notice);
    }
}
<?php

namespace app\model;

use think\Model;

class SystemModel extends Model
{
    protected $name = 'system';
    protected $pk = 'id';

    /** 获取ALL系统表
     * @return SystemModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllSystem(){
        return $this->select();
    }

    /** 获取活动表
     * @return SystemModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAchieve(){
        return $this->where('is_active', 1)->select();
    }

    /** 通过id获取表内容
     * @param int $id
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSystemById(int $id){
        return $this->find($id);
    }

    /** 保存系统表
     * @param array $data
     * @return bool
     */
    public function addSystem(array $data){
        return $this->save($data);
    }

    /** 启用系统表BY ID
     * @param int $id
     * @return bool
     */
    public function useSystemById(int $id): bool
    {
        // 启用此表的同时把除该id外的所有表is_active置为0
        $this->where('id', '<>', $id)->update(['is_active' => 0]);
        $status = $this->where('id', $id)->update(['is_active' => 1]);
        if (!$status || $status == 0) {
            return false;
        }
        return true;
    }

    /** 修改系统表
     * @param int $id
     * @param array $data
     * @return SystemModel
     */
    public function updateSystem(int $id, array $data){
        return $this->where('id', $id)->update($data);
    }

    /** 删除系统表
     * @param int $id
     * @return bool
     */
    public function deleteSystem(int $id){
        return $this->where('id', $id)->delete();
    }
}
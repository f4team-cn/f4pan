<?php

namespace app\model;

use think\Model;

class SvipModel extends Model
{
    protected $name = 'svip';
    protected $pk = 'id';

    /** 获取所有正常的SVIP信息
     * @return SvipModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllNormalSvips()
    {
        return $this->where('state', '0')->where('svip_end_time', '>', time())->select();
    }

    /** 获取所有账号
     * @return SvipModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllList(){
        return $this->select();
    }

    /** 根据id获取SVIP信息
     * @param int $id
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSvipById(int $id)
    {
        return $this->find($id);
    }

    /** 增加SVIP
     * @param array $data
     * @return bool
     */
    public function addSvip(array $data)
    {
        return $this->save($data);
    }

    /** 更新SVIP信息
     * @param int $id
     * @param array $data
     * @return SvipModel
     */
    public function updateSvip(int $id, array $data)
    {
        return $this->where('id', $id)->update($data);
    }

    /** 删除SVIP
     * @param int $id
     * @return bool
     */
    public function deleteSvip(int $id)
    {
        return $this->where('id', $id)->delete();
    }
}

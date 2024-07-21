<?php

namespace app\controller;

use app\BaseController;
use app\model\StatsModel;
use app\model\VisitModel;

class Statistics extends BaseController
{
    public function getIpCount(){
        $model = new VisitModel();
        $type = $model->count('DISTINCT ip_address');
        $count = $model->count('ip_address');
        return responseJson(200, 'success', ['ip_type' => $type, 'all_count' => $count]);
    }

    public function getIp(){
        $ip = $this->request->request("ip");
        //分页查询
        $size = $this->request->request("size")??10;
        $page = $this->request->request("page")??1;
        $model = new VisitModel();
        if (empty($ip)) {
            return responseJson(-1, '未传递参数');
        }
        $query = $model->page($page, $size)->select();
        return responseJson(200, 'success', $query);
    }
}
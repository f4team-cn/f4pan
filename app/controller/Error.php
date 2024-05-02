<?php

namespace app\controller;

use app\BaseController;

class Error extends BaseController
{
    public function index(): \think\Response
    {
        return responseJson(-1, '接口不存在');
    }
}
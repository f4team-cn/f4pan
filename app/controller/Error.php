<?php

namespace app\controller;

use app\BaseController;

class Error extends BaseController
{
    public function index(): \think\Response
    {
        if (file_exists(app()->getRootPath() . 'install.lock')){
            $install = true;
        }
        return responseJson(-1, '接口不存在', ['author'=>'F4Team', 'version'=>env('app.version'), 'installed'=> $install, 'github'=> 'https://github.com/f4team-cn/f4pan', 'website'=>'https://www.f4team.cn/']);
    }
}
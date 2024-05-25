<?php

namespace app\controller;

use app\BaseController;
use app\model\ApiKeyModel;
use app\model\NoticeModel;
use app\model\SvipModel;
use app\model\SystemModel;
use app\utils\JWTUtils;
use think\facade\Cache;
class Admin extends BaseController
{
    public function login()
    {
        $pwd = $this->request->param("pwd");
        $model = new SystemModel();
        $_pwd = $model->getAchieve()->toArray()[0]['admin_password'];
        if(empty($pwd)){
            return responseJson(-1 , '请输入密码');
        }
        if($pwd !== $_pwd){
            return responseJson(-1 , '登录失败');
        }else{
            $token = JWTUtils::getToken([
                'admin_login'=>true,
                'system_id'=>$model->getAchieve()->toArray()[0]['id'],
            ]);
            return responseJson(1 , '登录成功' , ['token'=>$token]);
        }
    }

    public function generateApiKey(){
        $model = new ApiKeyModel();
        $key = randomKey();
        $status = $model->addApiKey(['key'=>$key]);
        if (!$status){
            return responseJson(-1 , '生成失败');
        }
        return responseJson(1 , '生成成功' , ['key'=>$key]);
    }

    public function getApiKey(){
        $model = new ApiKeyModel();
        $data = $model->getAllApiKeys();
        return responseJson(1 , '获取成功' , $data);
    }

    public function addSvip(){
        $model = new SvipModel();
        $cookie = $this->request->param('cookie');
        if(empty($cookie)){
            return responseJson(-1 , 'cookie不能为空');
        }
        $account = accountStatus($cookie);
        if(!$account){
            return responseJson(-1 , 'cookie失效');
        }
        $model->addSvip([
            'name'=>$account['username'],
            'state'=>0,
            'cookie'=>$cookie,
            'add_time'=>time(),
            'svip_end_time'=>$account['end_time'],
            'vip_type'=>$account['is_vip'] ? '普通用户' : ($account['is_svip'] ? '超级会员' : ($account['is_evip'] ? 'EVIP' : '普通会员'))
        ]);
        return responseJson(1,"添.加.了.",$account);
    }

    public function deleteSvip(){
        $model = new SvipModel();
        $id = $this->request->param('id');
        if(empty($id)){
            return responseJson(-1 , 'id不能为空');
        }
        $status = $model->deleteSvip($id);
        if(!$status){
            return responseJson(-1,"不.存.在");
        }
        return responseJson(1,"删.除.了.");
    }

    public function updateSvip(){
        $model = new SvipModel();
        $id = $this->request->param('id');
        if(empty($id)){
            return responseJson(-1 , 'id不能为空');
        }
        $status = $model->updateSvip($id , [
            'state'=>$this->request->param('state')
        ]);
        if(!$status){
            return responseJson(-1,"不.存.在");
        }
        return responseJson(1,"更.新.了.");
    }

    public function getSvipList(){
        $model = new SvipModel();
        $data = $model->getAllNormalSvips();
        return responseJson(1 , '获取成功' , $data);
    }

    public function getAllNotice(){
        $model = new NoticeModel();
        return responseJson(1 , '获取成功' , $model->getAllNotice());
    }

    public function addNotice(){
        $model = new NoticeModel();
        $data = [
            'title'=>$this->request->param('title'),
            'content'=>$this->request->param('content'),
            'add_time'=>time()
        ];
        $status = $model->addNotice($data);
        if(!$status){
            return responseJson(-1,"添.加.失.败");
        }
        return responseJson(1,"添.加.了.", $data);
    }

    public function updateNotice(){
        $model = new NoticeModel();
        $id = $this->request->param('id');
        if(empty($id)){
            return responseJson(-1 , 'id不能为空');
        }
        $data = [
            'title'=>$this->request->param('title'),
            'content'=>$this->request->param('content'),
        ];
        $status = $model->updateNotice($id , $data);
        if(!$status){
            return responseJson(-1,"不.存.在");
        }
        return responseJson(1,"更.新.了.", $data);
    }

    public function deleteNotice(){
        $model = new NoticeModel();
        $id = $this->request->param('id');
        if(empty($id)){
            return responseJson(-1 , 'id不能为空');
        }
        $status = $model->deleteNotice($id);
        if(!$status){
            return responseJson(-1,"不.存.在");
        }
        return responseJson(1,"删.除.了.");
    }

    public function useNotice(){
        $model = new SystemModel();
        $notice_model = new NoticeModel();
        $id = $this->request->param('id');
        if(empty($id)){
            return responseJson(-1 , 'id不能为空');
        }
        if(!$notice_model->existNotice($id)){
            return responseJson(-1,"不.存.在");
        }
        $status = $model->updateSystem($model->getAchieve()->toArray()[0]['id'],['notice_id'=>$id]);
        if(!$status){
            return responseJson(-1,"出.错.了");
        }
        return responseJson(1,"使.用.了.");
    }

    public function getAllSystem(){
        $model = new SystemModel();
        return responseJson(1 , '获取成功' , $model->getAllSystem()->toArray());
    }

    public function addSystem(){
        $model = new SystemModel();
        $isActiveExists = $model->where('is_active', true)->count() > 0;
        $data = [
            'admin_password' => $this->request->param('admin_password'),
            'requires_key' => $this->request->param('requires_key') ?? 'dynamic',
            'notice_id' => $this->request->param('notice_id') ?? 0,
            'key_last_time' => $this->request->param('key_last_time') ?? 300,
            'fixed_key' => $this->request->param('fixed_key') ?? '',
            'real_url_last_time' => $this->request->param('real_url_last_time') ?? 1800,
            'parse_ua' => $this->request->param('parse_ua') ?? 'netdisk',
            'normal_cookie' => $this->request->param('normal_cookie') ?? '',
            'is_active' => !$isActiveExists // 如果有记录为true，则新记录为false；反之为true
        ];
        $status = $model->save($data);
        if (!$status) {
            return responseJson(-1, "添.加.失.败");
        }
        return responseJson(1, "添.加.了.", $data);
    }

    public function updateSystem(){
        $model = new SystemModel();
        $id = $this->request->param('id');
        if(empty($id)){
            return responseJson(-1 , 'id不能为空');
        }
        $data = [
            'admin_password' => $this->request->param('admin_password'),
            'requires_key' => $this->request->param('requires_key') ?? 'dynamic',
            'notice_id' => $this->request->param('notice_id') ?? 0,
            'key_last_time' => $this->request->param('key_last_time') ?? 300,
            'fixed_key' => $this->request->param('fixed_key') ?? '',
            'real_url_last_time' => $this->request->param('real_url_last_time') ?? 1800,
            'parse_ua' => $this->request->param('parse_ua') ?? 'netdisk',
            'normal_cookie' => $this->request->param('normal_cookie') ?? '',
        ];
        $status = $model->updateSystem($id , $data);
        if(!$status){
            return responseJson(-1,"不.存.在");
        }
        return responseJson(1,"更.新.了.", $data);
    }

    public function deleteSystem(){
        $model = new SystemModel();
        $id = $this->request->param('id');
        if(empty($id)){
            return responseJson(-1 , 'id不能为空');
        }
        $status = $model->deleteSystem($id);
        if(!$status){
            return responseJson(-1,"不.存.在");
        }
        return responseJson(1,"删.除.了.");
    }

    public function useSystem(){
        $model = new SystemModel();
        $id = $this->request->param('id');
        if(empty($id)){
            return responseJson(-1 , 'id不能为空');
        }
        $status = $model->useSystemById($id);
        if(!$status){
            return responseJson(-1,"出.错.了");
        }
        return responseJson(1,"使.用.了.");
    }
}
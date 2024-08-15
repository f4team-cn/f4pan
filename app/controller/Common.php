<?php

namespace app\controller;

use app\BaseController;
use app\model\ApiKeyModel;
use app\model\NoticeModel;
use app\model\StatsModel;
use app\model\SystemModel;

class Common extends BaseController
{
    public function getStatus()
    {
        $model = new StatsModel();
        $info = $model->where(1)->select()->toArray()[0];
        $format_size = formatSize($info['total_parsing_traffic']);
        return responseJson(200, "success", $info+['total_parsing_traffic_format' => $format_size]);
    }

    public function getSystem()
    {
        if (!file_exists(app()->getRootPath() . 'install.lock')){
           return responseJson(-1 , '系统还未安装');
        }
        $model = new SystemModel();
        $active = $model->getAchieve();
        $system = $active->toArray();
        unset($system[0]["normal_cookie"]);
        unset($system[0]["admin_password"]);
        unset($system[0]["fixed_key"]);
        return responseJson(200, '获.取.了.', $system[0]);
    }

    public function getNotice()
    {
        $model = new NoticeModel();
        $sysmodel = new SystemModel();
        //获取active系统表内notice_id对应的公告
        $active = $sysmodel->getAchieve()->toArray()[0];
        if ($active['notice_id'] == 0) {
            return responseJson(-1, '未.设.置.');
        }
        $notice = $model->getNoticeById($active['notice_id']);
        return responseJson(200, '获.取.了.', $notice);
    }

    public function getParseKey(){
        $apikey_model = new ApiKeyModel();
        $apikey = $this->request->param('apikey');
        if (empty($apikey)) {
            return responseJson(-1, '未.传.递.K.E.Y.');
        }
        if ($apikey_model->existApikey($apikey)) {
            $system_model = new SystemModel();
            $system = $system_model->getAchieve()->toArray()[0];
            $key = randomNumKey();
            $redis = \think\facade\Cache::store('redis');
            $redis->set($key, $apikey, $system['key_last_time']);
            $apikey_model->where('key', $apikey)->update(['use_count' => $apikey_model->where('key', $apikey)->value('use_count') + 1]);
            return responseJson(200, '获.取.了.', substr($key, -6));
        }
        return responseJson(-1, '未.查.到.K.E.Y.');
    }

    public function useParseKey(){
        $parse_key = $this->request->param('parse_key');
        $redis = \think\facade\Cache::store('redis');
        $model = new SystemModel();
        $system = $model->getAchieve()->toArray()[0];
        $surl = $this->request->param('surl');
        $pwd = $this->request->param('pwd');
        if (empty($surl)) {
            return responseJson(-1, '未传递参数');
        }
        if ($system['requires_key'] == 'dynamic') {
            if ($redis->has('f4pan_parse_key_' . $parse_key)) {
                $redis->delete($parse_key);
                $req_id = randomKey("f4pan_req_id_");
                $redis->set($req_id, $surl . '|' . $pwd, \think\facade\App::isDebug() ? 60 * 60 * 12 : 300);
                $redis->delete('f4pan_parse_key_' . $parse_key);
                return responseJson(200, '已使用KEY', $req_id);
            }
            return responseJson(-1, '未查到KEY');
        }elseif ($system['requires_key'] == 'fixed'){
            $key = $system['fixed_key'];
            if($key == $parse_key){
                $req_id = randomKey("f4pan_req_id_");
                $redis->set($req_id, $surl . '|' . $pwd, \think\facade\App::isDebug() ? 60 * 60 * 12 : 300);
                $redis->delete('f4pan_parse_key_' . $parse_key);
                return responseJson(200, '已使用KEY', $req_id);
            }else{
                return responseJson(-1, 'KEY错误');
            }
        }elseif ($system['requires_key'] == 'none'){
            $req_id = randomKey("f4pan_req_id_");
            $redis->set($req_id, $surl . '|' . $pwd, \think\facade\App::isDebug() ? 60 * 60 * 12 : 300);
            $redis->delete('f4pan_parse_key_' . $parse_key);
            return responseJson(200, '已生成req_id', $req_id);
        }
        return responseJson(-1, '未知错误');
    }
}

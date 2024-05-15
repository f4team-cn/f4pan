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
        $model = new SystemModel();
        $active = $model->getAchieve();
        return responseJson(1, '获.取.了.', $active->toArray());
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
        return responseJson(1, '获.取.了.', $notice);
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
            return responseJson(1, '获.取.了.', substr($key, -6));
        }
        return responseJson(-1, '未.查.到.K.E.Y.');
    }

    public function useParseKey(){
        $parse_key = $this->request->param('parse_key');
        $redis = \think\facade\Cache::store('redis');
        if ($redis->has('f4pan_parse_key_' . $parse_key)) {
            $redis->delete($parse_key);
            $surl = $this->request->param('surl');
            $pwd = $this->request->param('pwd');
            if (empty($surl) || empty($pwd)) {
                return responseJson(-1, '未.传.递.参.数.');
            }
            $req_id = randomKey("f4pan_req_id_");
            $redis->set($req_id, $surl . '|' . $pwd, 300);
            $redis->delete('f4pan_parse_key_' . $parse_key);
            return responseJson(1, '已.使.用.K.E.Y.', $req_id);
        }
        return responseJson(-1, '未.查.到.K.E.Y.');
    }
}
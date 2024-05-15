<?php

namespace app\controller;

use app\BaseController;
use app\model\StatsModel;
use app\model\SvipModel;
use app\model\SystemModel;
use app\utils\CurlUtils;
use think\App;
use think\validate\ValidateRule;

class Parse extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    private function getRandomSvipCookie(){
        $SvipModel = new SvipModel();
        $Svip = $SvipModel->getAllNormalSvips();
        $Svip = $Svip->toArray();
        if (!$Svip){
            return false;
        }
        $Svip_cookie = array_column($Svip, 'cookie');
        $Svip_id = array_column($Svip, 'id');
        $rand = array_rand($Svip);
        $Svip_cookie = $Svip_cookie[$rand];
        $info = accountStatus($Svip_cookie);
        $Svip_id = $Svip_id[$rand];
        if($info) {
            return [$Svip_cookie, $Svip_id];
        }else{
            $SvipModel->updateSvip($Svip_id, ['state' => -1]);
            $model = new StatsModel();
            $model->addSpentSvipCount();
            return false;
        }
    }

    private function getSign($share_id, $uk){
        $tplconfig = "https://pan.baidu.com/share/tplconfig?shareid={$share_id}&uk={$uk}&fields=sign,timestamp&channel=chunlei&web=1&app_id=250528&clienttype=0";
        $sign = CurlUtils::cookie(env('baidu.cookie'))->ua(env('baidu.ua'))->get($tplconfig)->obj(true);
        return $sign;
    }

    public function getFileList()
    {
        $shorturl = $this->request->shorturl;
        $password = $this->request->password;
        $isRoot = $this->request->isroot;
        $dir = $this->request->dir;
        $url = 'https://pan.baidu.com/share/wxlist?channel=weixin&version=2.2.2&clienttype=25&web=1';
        $root = ($isRoot) ? "1" : "0";
        $dir = urlencode($dir);
        $data = "shorturl=$shorturl&dir=$dir&root=$root&pwd=$password&page=1&num=1000&order=time";
        $time = time();
        $header = array(
            "User-Agent: netdisk",
            "Referer: https://pan.baidu.com/disk/home"
        );
        $result = CurlUtils::header($header)->cookie(env('baidu.cookie'))->post($url, $data)->obj(true);
        if ($result['errno'] != "0"){
            return json_encode(array('code'=>-1,'msg'=>'链接错误,请检查链接是否有效'),456);
        }
        foreach ($result['data']['list'] as $va){
            $filename = $va['server_filename'];
            $ctime = $va['server_ctime'];
            $path = $va['path'];
            $md5 = $va['md5']??"";
            $fs_id = $va['fs_id'];
            $isdir = $va['isdir'];
            $size = $va['size'];
            $array[] = array('filename'=>$filename,'ctime'=>$ctime,'path'=>$path,'md5'=>$md5,'fs_id'=>$fs_id,'isdir'=>$isdir,'size'=>$size);
        }
        if(!$array){
            $array = [];
        }
        $share_id = $result['data']['shareid'];
        $uk = $result['data']['uk'];
        $seckey = $result['data']['seckey'];
        $sign = $this->getSign($share_id, $uk);
        return responseJson(200, "获取成功", array('data'=>$array,'shareinfo'=>array('share_id'=>$share_id,'uk'=>$uk,'seckey'=>$seckey, 'sign'=>$sign['data'])));
    }

    public function parseFile()
    {
        $redis = \think\facade\Cache::store('redis');
        $fs_id = $this->request->param('fs_id');
        $timestamp = $this->request->param('timestamp');
        $sign = $this->request->param('sign');
        $randsk = $this->request->param('randsk');
        $share_id = $this->request->param('share_id');
        $uk = $this->request->param('uk');
        if (
            empty($fs_id) ||
            empty($timestamp) ||
            empty($sign) ||
            empty($randsk) ||
            empty($share_id) ||
            empty($uk)
        ) {
            return responseJson(-1, "缺少必要参数");
        }
        if($redis->get('parse_'.$fs_id)){
            $result = json_decode($redis->get('parse_'.$fs_id),true);
            $result['use_cache'] = true;
            return responseJson(200, "获取成功", $result);
        }
        if ($timestamp + 300 < time()){
            $tpl = $this->getSign($share_id, $uk)['data'];
            $sign = $tpl['sign'];
            $timestamp = $tpl['timestamp'];
        }
        $cookie = $this->getRandomSvipCookie();
        if (!$cookie){
            return responseJson(-1, "获取svip失败, 请重试");
        }
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.514.1919.810 Safari/537.36",
            "Referer: https://pan.baidu.com/disk/home"
        );
        $url = "https://pan.baidu.com/api/sharedownload?app_id=250528&channel=chunlei&clienttype=12&sign={$sign}&timestamp={$timestamp}&web=1";
        if (strstr($randsk, "%") != false) $randsk = urldecode($randsk);
        $data = [
            'encrypt' => '0',
            'extra' => urlencode('{"sekey":"' . $randsk . '"}'),
            'fid_list' => "[".$fs_id."]",
            'primaryid' => $share_id,
            'uk' => $uk,
            'product' => 'share',
            'type' => 'nolimit',
        ];
        $data = urldecode(http_build_query($data, '', '&', PHP_QUERY_RFC3986));
        $result = CurlUtils::header($header)->cookie(env('baidu.cookie'))->post($url, $data)->obj(true);
        if ($result['errno'] != 0){
            return responseJson(-1, "解析失败", $result);
        }
        $filename = $result["list"][0]["server_filename"];
        $filectime = $result["list"][0]["server_ctime"];
        $filemd5 = $result["list"][0]["md5"];
        $filesize = $result["list"][0]["size"];
        $url = $result["list"][0]["dlink"];
        $location = CurlUtils::ua(env('baidu.ua'))->cookie($cookie[0])->get($url)->head();
        if(!isset($location['Location'])){
            return responseJson(-1, "解析失败, 请重试");
        }
        $realLink = $location['Location'];
        if ($realLink == "" or str_contains($realLink, "qdall01.baidupcs.com") or !str_contains($realLink, 'tsl=0')) {
            $model = new SvipModel();
            $model->updateSvip($cookie[1], array('state' => -1));
            $model = new StatsModel();
            $model->addSpentSvipCount();
            return responseJson(-1, "解析失败，可能账号已限速，请3s后重试,账号ID{$cookie[1]}");
        }
        $result = array(
            'filename' => $filename,
            'filectime' => $filectime,
            'filemd5' => $filemd5,
            'filesize' => $filesize,
            'dlink' => $realLink,
            'ua' => env('baidu.ua'),
            'use_cache'=>false
        );
        $model = new SystemModel();
        $last_time = $model->getAchieve()->toArray()[0]['real_url_last_time'];
        $redis->set('parse_'.$fs_id, json_encode($result), $last_time);
        //进入统计
        $model = new StatsModel();
        $model->addParsingCount();
        $model->addTraffic($filesize);
        return responseJson(200, "获取成功", $result);
    }
}
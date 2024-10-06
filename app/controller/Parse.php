<?php

namespace app\controller;

use app\BaseController;
use app\model\StatsModel;
use app\model\SvipModel;
use app\model\SystemModel;
use app\utils\CurlUtils;
use think\App;


class Parse extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    private function getRandomSvipCookie() {
        $SvipModel = new SvipModel();
        $Svips = $SvipModel->getAllNormalSvips()->toArray();
        if (empty($Svips)) {
            $Svips = $SvipModel->getAllList()->toArray();
        }
        if (empty($Svips)) {
            return false;
        }
        $randIndex = array_rand($Svips);
        $Svip = $Svips[$randIndex];

        $Svip_cookie = $Svip['cookie'];
        $Svip_id = $Svip['id'];
        $info = accountStatus($Svip_cookie);
        if ($info) {
            return [$Svip_cookie, $Svip_id];
        } else {
            $SvipModel->updateSvip($Svip_id, ['state' => -1]);
            (new StatsModel())->addSpentSvipCount();
            return false;
        }
    }


    private function getSign($share_id, $uk){
        $tplconfig = "https://pan.baidu.com/share/tplconfig?shareid={$share_id}&uk={$uk}&fields=sign,timestamp&channel=chunlei&web=1&app_id=250528&clienttype=0";
        $sign = CurlUtils::cookie(SystemModel::getNormalCookie())->ua(SystemModel::getUa())->get($tplconfig)->obj(true);
        return $sign;
    }
    
    public function getFileList()
    {
        $shorturl = $this->request->surl;
        $password = $this->request->password;
        $isRoot = $this->request->isroot;
        $dir = $this->request->dir;
        $url = 'https://pan.baidu.com/share/wxlist?channel=weixin&version=2.2.2&clienttype=25&web=1';
        $root = ($isRoot) ? "1" : "0";
        $dir = urlencode($dir);
        $data = "shorturl=$shorturl&dir=$dir&root=$root&pwd=$password&page=1&num=1000&order=time";
        $header = array(
            "User-Agent: netdisk",
            "Referer: https://pan.baidu.com/disk/home"
        );
        $result = CurlUtils::header($header)->cookie(SystemModel::getNormalCookie())->post($url, $data)->obj(true);
        if ($result['errno'] != "0"){
            return responseJson(-1, '链接错误,请检查链接是否有效');
        }
        foreach ($result['data']['list'] as $va){
            $filename = $va['server_filename'];
            $ctime = $va['server_ctime'];
            $path = $va['path'];
            $md5 = $va['md5']??"";
            $fs_id = (int)$va['fs_id'];
            $isdir = $va['isdir'];
            $size = (int)$va['size'];
            $array[] = array('filename'=>$filename,'ctime'=>$ctime,'path'=>$path,'md5'=>$md5,'fs_id'=>$fs_id,'isdir'=>$isdir,'size'=>$size);
        }
        if(!$array){
            $array = [];
        }
        $share_id = $result['data']['shareid'];
        $uk = $result['data']['uk'];
        $seckey = $result['data']['seckey'];
        $seckey = str_replace("-", "+", $seckey);
        $seckey = str_replace("~", "=", $seckey);
        $seckey = str_replace("_", "/", $seckey);
        return responseJson(200, "获取成功", array('list'=>$array,'shareinfo'=>array('share_id'=>$share_id,'uk'=>$uk,'seckey'=>$seckey)));
    }

    //6.22更新
    public function parseFile()
    {
        $redis = \think\facade\Cache::store('redis');
        $fs_id = $this->request->fs_id;
        $randsk = $this->request->randsk;
        $share_id = $this->request->share_id;
        $uk = $this->request->uk;
        $surl = $this->request->surl;
        $short = $this->request->short;
        if (
            empty($fs_id) ||
            empty($surl) ||
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
        $cookie = $this->getRandomSvipCookie();
        if (!$cookie){
            return responseJson(-1, "获取可用账号失败");
        }
        if(!self::checkDir($cookie[0])){
            if(!self::createNewDir($cookie[0])){
                return responseJson(-1, "创建文件夹失败");
            };
        }
        $url = 'https://pan.baidu.com/s/'.$surl;
        $array  = self::transfer($cookie,$share_id,$uk,$fs_id,$randsk,$url);
        $to_fs_id = $array['to_fs_id'];
        $to_path = $array['to_path'];
        if (!$to_fs_id){
            $id = $cookie[1];
            $model = new SvipModel();
            $model->updateSvip($id, ['state' =>-1]);
            $model_ = new StatsModel();
            $model_->addSpentSvipCount();
            $cookie = $this->getRandomSvipCookie();
            if(!$cookie){
                return responseJson(-1, "获取可用账号失败");
            }
            $cookie = $this->getRandomSvipCookie();
            $array  = self::transfer($cookie,$share_id,$uk,$fs_id,$randsk,$url);
            $to_fs_id = $array['to_fs_id'];
            $to_path = $array['to_path'];
            if (!$to_path){
                $model = new SvipModel();
                $model->updateSvip($id, ['state' =>-1]);
                return responseJson(-1, "转移文件失败，请检查解析账号可用空间\n暂不支持解析 解析账号分享的文件");
            }
        }
        $to_path = rawurlencode($to_path);
        $url = "https://pcs.baidu.com/rest/2.0/pcs/file?method=locatedownload&app_id=250528&path=$to_path&ver=2&time=1676908121&rand=df142c666096ad54f9a9f2de21b02d37d9205722&devuid=O%7C0D9FD9F4941FF7A591BB2A8682D18629";
        $res = getUrlCurl($url, SystemModel::getUa(), $cookie[0]);
        $realLink = $res['urls'][0]['url'];
        if (str_contains($realLink, "nd6.baidupcs.com") && count($res["urls"]) > 1){
            $realLink = $res['urls'][rand(1, count($res["urls"])-1)]['url'];
        }
        preg_match("/size=(\d+)/", $realLink, $pp);
        $filesize = $pp[1];
        preg_match("/&fin=(.+)&bflag/", $realLink, $pp);
        $filename = $pp[1];
        $filename = urldecode($filename);
        preg_match("/\/file\/(.+)\?/", $realLink, $pp);
        $filemd5 = $pp[1];
        preg_match("/ctime=(\d+)/", $realLink, $pp);
        $filectime = $pp[1];
        
        if ($realLink == "" or str_contains($realLink, "qdall01.baidupcs.com")) {
            $model = new SvipModel();
            $model->updateSvip($cookie[1], array('state' => -1));
            $model = new StatsModel();
            $model->addSpentSvipCount();
//            print_r($realLink);
            return responseJson(-1, "解析失败，可能账号已限速，请3s后重试,账号ID{$cookie[1]}");
        }

        $realLink = is_true($short) ? self::createShortUrl($realLink, $surl, (int)$fs_id) : $realLink;
        $result = array(
            'filename' => $filename,
            'filectime' => $filectime,
            'filemd5' => $filemd5,
            'filefsid' => (int)$fs_id,
            'filesize' => $filesize,
            'dlink' => $realLink,
            'ua' => SystemModel::getUa(),
            'use_cache'=>false
        );
        $model = new SystemModel();
        $last_time = $model->getAchieve()->toArray()[0]['real_url_last_time'];
        $redis->set('parse_'.$fs_id, json_encode($result), $last_time);
        //进入统计
        $model = new StatsModel();
        $model->addParsingCount();
        $model->addTraffic($filesize);
        //每日统计
        $today = date('Y-m-d');
        $redisKey = 'download_traffic_' . $today;
        $currentFlow = $redis->get($redisKey);
        if (!$currentFlow) {
            $currentFlow = 0;
        }
        $newFlow = $currentFlow + $filesize;
        $redis->set($redisKey, $newFlow, strtotime('tomorrow') - 1);
        return responseJson(200, "获取成功", $result);
    }

    public function shortUrlRedirect(string $code)
    {
        $redis = \think\facade\Cache::store('redis');
        $url = $redis->get("short_url_" . $code);
        if ($url) {
            return redirect($url);
        } else {
            return responseJson(-1, "短链接不存在或已过期", ['code'=>$code]);
        }
    }

    private static function createShortUrl(string $url, string $surl, int $fsid): string
    {
        $redis = \think\facade\Cache::store('redis');
        $shortCode = $surl . ':' . $fsid;
        $model = new SystemModel();
        $last_time = $model->getAchieve()->toArray()[0]['real_url_last_time'];
        $redis->set("short_url_" . $shortCode, $url, $last_time);
        return request()->domain() . '/api/v1/s/' . $shortCode;
    }

    public static function checkDir($cookie){
        $url = 'https://pan.baidu.com/api/list?channel=chunlei&bdstoken=e6bc800efaabbc3b1b07952bedc1d445&app_id=250528&dir=%2F&order=name&desc=0&start=0&limit=500&t=0.5963396759604782&channel=chunlei&web=1&bdstoken=e6bc800efaabbc3b1b07952bedc1d445&logid=RENBODQ1MkY3Mzg4MEMzOUUzOTBCQ0JCRDM0NEYwMzY6Rkc9MQ==&clienttype=0&dp-logid=93935300557954940027';
        $res = CurlUtils::cookie($cookie)->get($url)->obj(true);
        foreach ($res['list'] as $k=>$va){
            if($va['path'] == "/parse_file" && $va['isdir'] == 1){
                return true;
            }
        }
        return false;
    }

    public static function createNewDir($cookie){
        $url = 'https://pan.baidu.com/api/create?a=commit&channel=chunlei&bdstoken=e6bc800efaabbc3b1b07952bedc1d445&app_id=250528&channel=chunlei&web=1&bdstoken=e6bc800efaabbc3b1b07952bedc1d445&logid=RENBODQ1MkY3Mzg4MEMzOUUzOTBCQ0JCRDM0NEYwMzY6Rkc9MQ==&clienttype=0&dp-logid=25871100140032000048';
        $res = CurlUtils::cookie($cookie)->post($url, 'path=//parse_file&isdir=1&size=&block_list=[]&method=post&dataType=json')->obj(true);
        if ($res['errno'] == 0){
            return true;
        }
        return false;
    }

    public static function transfer($cookie, $shareid, $from, $fsid, $randsk, $shareurl){
        $bdstoken = CurlUtils::cookie($cookie[0])->get("https://pan.baidu.com/api/gettemplatevariable?clienttype=0&app_id=250528&web=1&fields=[%22bdstoken%22,%22token%22,%22uk%22,%22isdocuser%22,%22servertime%22]")->obj(true);
        $bdstoken = $bdstoken['result']['bdstoken'];
        $randsk = urlencode($randsk);
        $url = "https://pan.baidu.com/share/transfer?shareid=$shareid&from=$from&channel=chunlei&sekey=$randsk&ondup=newcopy&web=1&app_id=250528&bdstoken=$bdstoken&logid=QTU4NjczRTM3OEFDNkI1NUQ0QzExQ0VFOEY5M0VGREQ6Rkc9MQ==&clienttype=0";
        $cookie[0] .= ";BDCLND=$randsk";
        $header =
            [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36 Edg/110.0.1587.63',
                'Connection: Keep-Alive',
                'Content-Type: application/x-www-form-urlencoded'
            ];
        $data = array(
            'fsidlist'=>"[$fsid]",
            'path'=>'/parse_file'
        );
        $res = CurlUtils::cookie($cookie[0])->header($header)->post($url, $data)->referer($shareurl)->obj(true);
        if($res['errno'] == 9013){
            $model = new SvipModel();
            $model->updateSvip($cookie[1], array('state' => -1));
            return array('to_path'=>null,'to_fs_id'=>null,'cookie'=>$cookie);;
        }
        if($res['errno'] == 12){
            $model = new SvipModel();
            $model->updateSvip($cookie[1], array('state' => -1));
            return array('to_path'=>null,'to_fs_id'=>null,'cookie'=>$cookie);;
        }
        if($res['errno'] == 2){
            return array('to_path'=>null,'to_fs_id'=>null,'cookie'=>$cookie);;
        }
        $to_path = $res['extra']['list'][0]['to'];
        $to_fs_id = $res['extra']['list'][0]['to_fs_id'];
        return array('to_path'=>$to_path,'to_fs_id'=>$to_fs_id,'cookie'=>$cookie);
    }
}

<?php

namespace app\controller;

use app\BaseController;
use app\model\StatsModel;
use app\model\StatsDailyModel;
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
        $array = [];
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
        $url = "https://d.pcs.baidu.com/rest/2.0/pcs/file?ant=1&apn_id=33_13&app_id=250528&channel=0&check_blue=1&clienttype=17&cuid=08E271F7046B366BE1BF9F1F30DF0689%7CVFZVYSRCU&deviceid=611777535803319847&devuid=08E271F7046B366BE1BF9F1F30DF0689%7CVFZVYSRCU&dtype=1&eck=1&ehps=1&err_ver=1.0&es=1&esl=1&freeisp=0&method=locatedownload&network_type=4G&path=$to_path&queryfree=0&rand=0854bec9ad10241680eb16aaf3e9ab3912f0f429&time=1744558717&use=0&ver=4.0&version=2.2.101.242&version_app=12.25.3&vip=0&psign=aa42ffc322b4c71d2f39e422aa83607e";
        $res = getUrlCurl($url, SystemModel::getUa(), $cookie[0]);
        if (!isset($res['urls'])) {
            $msg = isset($res['errmsg']) ? $res['errmsg'] : '未知错误';
            return responseJson(-1, $msg, $res);
        }
        $realLink = $res['urls'][0]['url'];
        if (str_contains($realLink, "nd6.baidupcs.com") && count($res["urls"]) > 1){
            $realLink = $res['urls'][rand(1, count($res["urls"])-1)]['url'];
        }
        $realLink .= "&origin=dlna";
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
        $stats = new StatsDailyModel();
        $stats->addTraffic($filesize);
        $stats->addParsingCount();
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
        $ua = "User-Agent: Mozilla/5.0 (Linux; Android 6.0.1; OPPO R9s Plus Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36";
        $res = getUrlCurl($url, $ua, $cookie);
        foreach ($res['list'] as $k=>$va){
            if($va['path'] == "/parse_file" && $va['isdir'] == 1){
                return true;
            }
        }
        return false;
    }

    public static function createNewDir($cookie){
        $url = 'https://pan.baidu.com/api/create?a=commit&channel=chunlei&bdstoken=e6bc800efaabbc3b1b07952bedc1d445&app_id=250528&channel=chunlei&web=1&bdstoken=e6bc800efaabbc3b1b07952bedc1d445&logid=RENBODQ1MkY3Mzg4MEMzOUUzOTBCQ0JCRDM0NEYwMzY6Rkc9MQ==&clienttype=0&dp-logid=25871100140032000048';
        $ua = "User-Agent: Mozilla/5.0 (Linux; Android 6.0.1; OPPO R9s Plus Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36";
        //$res = CurlUtils::cookie($cookie)->post($url, 'path=//parse_file&isdir=1&size=&block_list=[]&method=post&dataType=json')->obj(true);
        $res = postUrlCurl($url, $ua, $cookie, "path=//parse_file&isdir=1&size=&block_list=[]&method=post&dataType=json");
        if ($res['errno'] == 0){
            return true;
        }
        return false;
    }

    public static function transfer($cookie, $shareid, $from, $fsid, $randsk, $shareurl){
        $ua = "User-Agent: Mozilla/5.0 (Linux; Android 6.0.1; OPPO R9s Plus Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36";
        $bdstoken = getUrlCurl("https://pan.baidu.com/api/gettemplatevariable?clienttype=0&app_id=250528&web=1&fields=[%22bdstoken%22,%22token%22,%22uk%22,%22isdocuser%22,%22servertime%22]", $ua, $cookie[0]);
        $bdstoken = $bdstoken['result']['bdstoken'];
        $randsk = urlencode($randsk);
        $cookie[0] .= ";BDCLND=$randsk";
        //curl
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://pan.baidu.com/share/transfer?shareid=$shareid&from=$from&sekey=$randsk&ondup=newcopy&async=1&channel=chunlei&web=1&app_id=250528&bdstoken=$bdstoken&logid=N0Y0NTVBMDg1NkZFNDVFMjVEQzYxMUE0OUUwMEM5QzM6Rkc9MQ%3D%3D&clienttype=0&dp-logid=48806300903566960041",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "fsidlist=[$fsid]&path=/parse_file&type=1'",
            CURLOPT_COOKIE => $cookie[0],
            CURLOPT_HTTPHEADER => [
              'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36',
              'Accept: application/json, text/javascript, */*; q=0.01',
              'Accept-Encoding: gzip, deflate, br, zstd',
              'Content-Type: application/x-www-form-urlencoded',
              'sec-ch-ua-platform: "Android"',
              'X-Requested-With: XMLHttpRequest',
              'sec-ch-ua: "Android WebView";v="135", "Not-A.Brand";v="8", "Chromium";v="135"',
              'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
              'sec-ch-ua-mobile: ?1',
              'Origin: https://pan.baidu.com',
              'Sec-Fetch-Site: same-origin',
              'Sec-Fetch-Mode: cors',
              'Sec-Fetch-Dest: empty',
              'Referer: '.$shareurl,
              'Accept-Language: zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7',
           ],
        ]);

        $res = json_decode(curl_exec($curl), true);
        curl_close($curl);
        //print_r($res);
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

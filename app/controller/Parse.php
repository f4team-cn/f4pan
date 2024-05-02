<?php

namespace app\controller;

use app\BaseController;
use app\model\SvipModel;
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
        $Svip_cookie = array_column($Svip, 'cookie');
        $Svip_id = array_column($Svip, 'id');
        $rand = array_rand($Svip);
        $Svip_cookie = $Svip_cookie[$rand];
        $Svip_id = $Svip_id[$rand];
        return [$Svip_cookie, $Svip_id];
    }

    private function getSign($share_id, $uk){
        $tplconfig = "https://pan.baidu.com/share/tplconfig?shareid={$share_id}&uk={$uk}&fields=sign,timestamp&channel=chunlei&web=1&app_id=250528&clienttype=0";
        $sign = CurlUtils::cookie($this->getRandomSvipCookie())->ua("netdisk")->get($tplconfig)->obj(true);
        return $sign;
    }

    public function getFileList()
    {
        $shorturl = $this->request->request('shorturl');
        $dir = $this->request->request('dir')??'';
        $password = $this->request->request('password');
        $isRoot = $this->request->request('isroot')??true;
        if (!isset($shorturl) || !isset($password)) {
            return responseJson(-1, "error, 缺少必要参数 shorturl 或 password");
        }
        if ($isRoot === null && $dir === null) {
            return responseJson(-1, "error, 请同时传入 dir 和 isroot");
        }
        $url = 'https://pan.baidu.com/share/wxlist?channel=weixin&version=2.2.2&clienttype=25&web=1';
        $root = ($isRoot) ? "1" : "0";
        $dir = urlencode($dir);
        $data = "shorturl=$shorturl&dir=$dir&root=$root&pwd=$password&page=1&num=1000&order=time";
        $time = time();
        $header = array(
            "User-Agent: netdisk",
            "Referer: https://pan.baidu.com/disk/home"
        );
        $result = CurlUtils::header($header)->cookie(env('cookie'))->post($url, $data)->obj(true);
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
        if ($timestamp + 300 < time()){
            $tpl = $this->getSign($share_id, $uk)['data'];
            $sign = $tpl['sign'];
            $timestamp = $tpl['timestamp'];
        }
        $cookie = $this->getRandomSvipCookie();
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.514.1919.810 Safari/537.36",
            "Referer: https://pan.baidu.com/disk/home"
        );
        $url = "https://pan.baidu.com/api/sharedownload?app_id=250528&channel=chunlei&clienttype=12&sign={$sign}&timestamp={$timestamp}&web=1";
        if (strstr($randsk, "%") != false) $randsk = urldecode($randsk);
        $data = [
            'encrypt' => '0',
            'extra' => urlencode('{"sekey":"' . $randsk . '"}'),
            'fid_list' => urlencode("[").$fs_id.urlencode("]"),
            'primaryid' => $share_id,
            'uk' => $uk,
            'product' => 'share',
            'type' => 'nolimit',
        ];
        $data = urldecode(http_build_query($data, '', '&', PHP_QUERY_RFC3986));
        $result = CurlUtils::header($header)->cookie(env('cookie'))->post($url, $data)->obj(true);
        $filename = $result["list"][0]["server_filename"];
        $filectime = $result["list"][0]["server_ctime"];
        $filemd5 = $result["list"][0]["md5"];
        $filesize = $result["list"][0]["size"];
        $url = $result["list"][0]["dlink"];
        $realLink = CurlUtils::ua("netdisk")->cookie($cookie[0])->get($url)->head()['Location'];
        if ($realLink == "" or str_contains($realLink, "qdall01.baidupcs.com") or !str_contains($realLink, 'tsl=0')) {
            SvipModel::updateSvip($cookie[1], array('state' => -1));
            return responseJson(-1, "解析失败，可能账号已限速，请3s后重试,账号ID{$cookie[1]}");
        }
        $result = array(
            'filename' => $filename,
            'filectime' => $filectime,
            'filemd5' => $filemd5,
            'filesize' => $filesize,
            'dlink' => $realLink,
        );
        return responseJson(200, "获取成功", $result);
    }
}
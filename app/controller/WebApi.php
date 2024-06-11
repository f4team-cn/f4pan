<?php

namespace app\controller;

use app\BaseController;
use app\utils\CurlUtils;
use think\App;

//此方案废弃
class WebApi extends BaseController
{
//    private $curl = null;
    public function __construct(App $app)
    {
        parent::__construct($app);
//        $this->curl = new CurlUtils();
    }

    public function getQrcode(){
        $url = "https://passport.baidu.com/v2/api/getqrcode?lp=pc&qrloginfrom=pc&gid=197993A-8152-4BFA-A432-E2D84CF46E3C&oauthLog=&apiver=v3&tt=1675336415350&tpl=netdisk&logPage=traceId:pc_loginv4_1675336222,logPage:loginv4&_=1675336415355";
        $data = CurlUtils::get($url)->obj(true);
        $imgurl = $data["imgurl"];
        $imgurl = "http://".$imgurl;
        $sign = $data["sign"];
        return responseJson(200,"获取成功",array("imgurl"=>$imgurl, "sign"=>$sign));
    }
    public function unicast(){
        $sign = $this->request->request('sign');
        $url = "https://passport.baidu.com/channel/unicast?channel_id=".$sign."&gid=9B53B82-019B-47C2-AA3E-9D5D9D10F1E5&tpl=netdisk&_sdkFrom=1&apiver=v3&tt=1714656808250&_=1714656808250";
        $data = CurlUtils::timeout(60)->get($url)->obj(true);
//        var_dump($data);
        if($data["errno"] == 0){
            $channel_v = $data["channel_v"];
            $channel_v = json_decode($channel_v,true);
            if($channel_v["status"] == 0){
                $v = $channel_v["v"];
                return responseJson(200,"获取成功",array("bduss"=>$v));
            }
            return responseJson(-1,"请在百度网盘客户端确认登陆");
        }
        return responseJson(-2,"请在百度网盘客户端扫码");
    }
    public function qrcodeLogin(){
        $bduss = $this->request->request('bduss');
        $time = time();
        $ttime = time().rand(111,999);
        $url = 'https://passport.baidu.com/v3/login/main/qrbdusslogin?v='.$ttime.'&bduss='.$bduss.'&u=https%253A%252F%252Fpan.baidu.com%252Fdisk%252Fmain%253Ffrom%253DhomeFlow%2523%252Findex&loginVersion=v4&qrcode=1&tpl=netdisk&apiver=v3&tt='.$ttime.'&traceid=&time='.$time.'&alg=v3&sig=dCtHYWdSUjNXL3pzTk1Cb0F2ZmxWdGJObUNTQTR4VlRITndidlhzMmI1L3Z0ZTR0NHpneXdlVDhXWE1jbWgwdg%3D%3D&elapsed=176&shaOne=006de51bd08c3ee46a42b84cf525dac1ddcfa6dc&rinfo=%7B%22fuid%22%3A%22afead616eeadbcebf9985529945cca45%22%7D';
        $url_res = CurlUtils::get($url)->head()['Set-Cookie'];
        $cookie = "";
        foreach($url_res as $k){
            $cookie .= $k."; ";
        }
        $url = 'https://passport.baidu.com/v3/login/api/auth/?return_type=5&tpl=netdisk&u=https%3A%2F%2Fpan.baidu.com%2Fdisk%2Fmain';
        $loadurl = CurlUtils::cookie($cookie)->get($url)->location(false)->all();
        $stoken = CurlUtils::cookie($cookie)->location(false)->get($loadurl['headers']['Location'])->all();
        preg_match('/BDUSS=(.*?);/i', $cookie, $matches);
        if(!@$matches[1]){
            return responseJson(-1,"获取失败");
        }
        $cookie = 'BDUSS='.$matches[1].';STOKEN='.$stoken['cookie']['STOKEN'];
        return responseJson(200,"获取成功",array("cookie"=>$cookie));
    }
}
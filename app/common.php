<?php
// 应用公共文件

use app\model\SvipModel;
use app\utils\CurlUtils;

function responseJson(int $code = 200, string $message = '操作成功', mixed $data = []): \think\Response
{
        return \think\Response::create([
                'code' => $code,
                'message' => $message,
                'data' => $data
        ], 'json');
}

function randomKey(string $text = 'f4pan_apikey_') {
    $randomLetters = bin2hex(random_bytes(8));
    $apiKey = $text . $randomLetters;
    return $apiKey;
}

function randomNumKey(string $text = 'f4pan_parse_key_'){
    //六位数字
    $randomNumbers = mt_rand(100000, 999999);
    $parseKey = $text . $randomNumbers;
    return $parseKey;
}

//function getAccessToken($localstate, $id = null){
//    $url = 'http://127.0.0.1:8003/get_access_token';//使用公共的获取服务
//    $res = CurlUtils::post($url, $localstate)->obj(true);
//    $model = new SvipModel();
//    if($id){
//        $model->updateSvip($id, ['access_token' => $res['access_token'], 'local_state'=>$res['localstate']]);
//    }
//    return $res;
//}

function accountStatus(string $cookie, $localstate=null){
    $url = "https://pan.baidu.com/api/gettemplatevariable?channel=chunlei&web=1&app_id=250528&clienttype=0&fields=[%22username%22,%22loginstate%22,%22is_vip%22,%22is_svip%22,%22is_evip%22]";
    $result = CurlUtils::ua('pc')->cookie($cookie)->get($url)->obj(true);
    if($result['errno'] == -6){
        return false;
    }
    if($result['result']['is_svip']){
        $url_ = "https://pan.baidu.com/rest/2.0/membership/user?method=query&clienttype=0&app_id=250528&web=1";
        $end_time = CurlUtils::ua('pc')->cookie($cookie)->get($url_)->obj(true)['product_infos'];
        foreach ($end_time as $item){
            if($item['detail_cluster'] == 'svip'){
                $end_time = $item;
                break;
            }
        }
        $end_time = $end_time['end_time'];
//        if ($localstate){
//            $access = ['access_token'=>getAccessToken($localstate)['access_token']];
//            return $result['result']+['end_time'=>$end_time]+$access;
//        }
        return $result['result']+['end_time'=>$end_time];
    }
    return $result['result']+['end_time'=>0];
}

function formatSize(float $size, int $times = 0)
{
    if ($size > 1024) {
        $size /= 1024;
        return formatSize($size, $times + 1); // 递归处理
    } else {
        switch ($times) {
            case '0':
                $unit = ($size == 1) ? 'Byte' : 'Bytes';
                break;
            case '1':
                $unit = 'KB';
                break;
            case '2':
                $unit = 'MB';
                break;
            case '3':
                $unit = 'GB';
                break;
            case '4':
                $unit = 'TB';
                break;
            case '5':
                $unit = 'PB';
                break;
            case '6':
                $unit = 'EB';
                break;
            case '7':
                $unit = 'ZB';
                break;
            default:
                $unit = '单位未知';
        }
        return sprintf('%.2f', $size) . $unit;
    }
}

function getUrlCurl($url, $ua, $cookie){
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_COOKIE => $cookie,
        CURLOPT_HTTPHEADER => [
            'User-Agent: '.$ua,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Cache-Control: max-age=0',
            'sec-ch-ua: "Microsoft Edge";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-User: ?1',
            'Sec-Fetch-Dest: document',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
        ],
    ]);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);
    return $response;
}

function is_true($val, $return_null=false){
    $boolval = ( is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val );
    return ( $boolval===null && !$return_null ? false : $boolval );
}
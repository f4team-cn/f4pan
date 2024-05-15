<?php
// 应用公共文件

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

function accountStatus(string $cookie){
    $url = "https://pan.baidu.com/api/gettemplatevariable?channel=chunlei&web=1&app_id=250528&clienttype=0";
    $data = "fields=[%22username%22,%22loginstate%22,%22is_vip%22,%22is_svip%22,%22is_evip%22]";
    $result = CurlUtils::ua('pc')->cookie($cookie)->post($url, $data)->obj(true);
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
        return $result['result']+['end_time'=>$end_time];
    }
    return $result['result']+['end_time'=>0];
}
<?php

namespace app\middleware;
use app\utils\JWTUtils;

class AuthMiddleware
{
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Authorization');
        if(empty($token)){
            return responseJson(401 , '请进行管理员登录');
        }else{
            $res = JWTUtils::getPayloadAndVerify($token);
            if($res === 1){
                return responseJson(401 , '请进行管理员登录');
            }elseif($res === 2){
                return responseJson(401 , '请进行管理员登录');
            }elseif($res === 3){
                return responseJson(401 , '您的登录过期');
            }elseif($res === 4){
               return responseJson(401 , '您的登录过期');
           }elseif($res === 5){
                return responseJson(401 , '您的登录过期');
            }
            $request->login = $res;
            return $next($request);
       }
    }
}
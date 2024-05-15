<?php

namespace app\middleware;

use app\model\SystemModel;

class ParseMiddleware
{
    public function handle($request, \Closure $next){
        $model = new SystemModel();
        $system = $model->getAchieve()->toArray()[0];
        if($system['requires_key'] == "fixed"){
            $key = $system['fixed_key'];
            if($key == $request->param('key')){
                $shorturl = $request->request('shorturl');
                $dir = $request->request('dir')??'';
                $password = $request->request('password');
                $isRoot = $request->request('isroot')??true;
                $sign = $request->request('sign');
                if(!$sign){
                    if (!isset($shorturl) || !isset($password)) {
                        return responseJson(-1, "error, 缺少必要参数 shorturl 或 password");
                    }
                }
                $request->shorturl = $shorturl;
                $request->password = $password;
                $request->isroot = $isRoot;
                $request->dir = $dir;
                return $next($request);
            }else{
                return responseJson(-1, "key错误");
            }
        }elseif($system['requires_key'] == "dynamic"){
            $req_id = $request->request('req_id');
            if(!isset($req_id)){
                return responseJson(-1, "error, 缺少必要参数 req_id");
            }
            $redis = \think\facade\Cache::store('redis');
            if(!$redis->has($req_id)){
                return responseJson(-1, "error, 请先使用 动态密码");
            }
//            $this->data->req_id = $req_id;
            [$shorturl, $password] =  explode('|',$redis->get($req_id));
            $dir = $request->request('dir')??'';
            $isRoot = $request->request('isroot')??true;
            $request->shorturl = $shorturl;
            $request->password = $password;
            $request->isroot = $isRoot;
            $request->dir = $dir;
            return $next($request);
        }elseif ($system['requires_key'] == "none"){
            $shorturl = $request->request('shorturl');
            $dir = $request->request('dir')??'';
            $password = $request->request('password');
            $isRoot = $request->request('isroot')??true;
            $sign = $request->request('sign');
            if(!$sign){
                if (!isset($shorturl) || !isset($password)) {
                    return responseJson(-1, "error, 缺少必要参数 shorturl 或 password");
                }
            }
            $request->shorturl = $shorturl;
            $request->password = $password;
            $request->isroot = $isRoot;
            $request->dir = $dir;
            return $next($request);
        }
        return $next($request);
    }

}
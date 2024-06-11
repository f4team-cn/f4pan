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
                $dir = $request->request('dir')??'';
                $password = $request->request('password');
                $isRoot = $request->request('isroot')??true;
                $sign = $request->request('sign');
                $surl = $request->request('surl');
                $fs_id = $request->request('fs_id')??'';
                $randsk = $request->request('randsk')??'';
                $share_id = $request->request('share_id')??'';
                $uk = $request->request('uk')??'';
                if (!isset($surl) || !isset($password)) {
                    return responseJson(-1, "error, 缺少必要参数 surl 或 password");
                }
                $request->surl = $surl;
                $request->password = $password;
                $request->isroot = $isRoot;
                $request->dir = $dir;
                $request->surl = $surl;
                $request->fs_id = $fs_id;
                $request->randsk = $randsk;
                $request->share_id = $share_id;
                $request->uk = $uk;
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
            [$surl, $password] =  explode('|',$redis->get($req_id));
            $dir = $request->request('dir')??'';
            $isRoot = $request->request('isroot')??true;
            $fs_id = $request->request('fs_id')??'';
            $randsk = $request->request('randsk')??'';
            $share_id = $request->request('share_id')??'';
            $uk = $request->request('uk')??'';
            $request->surl = $surl;
            $request->password = $password;
            $request->isroot = $isRoot;
            $request->dir = $dir;
            $request->surl = $surl;
            $request->fs_id = $fs_id;
            $request->randsk = $randsk;
            $request->share_id = $share_id;
            $request->uk = $uk;
            return $next($request);
        }elseif ($system['requires_key'] == "none"){
            $dir = $request->request('dir')??'';
            $password = $request->request('password');
            $isRoot = $request->request('isroot')??true;
            $sign = $request->request('sign');
            $surl = $request->request('surl');
            $fs_id = $request->request('fs_id')??'';
            $randsk = $request->request('randsk')??'';
            $share_id = $request->request('share_id')??'';
            $uk = $request->request('uk')??'';
            if (!isset($surl) || !isset($password)) {
                return responseJson(-1, "error, 缺少必要参数 surl 或 password");
            }
            $request->surl = $surl;
            $request->password = $password;
            $request->isroot = $isRoot;
            $request->dir = $dir;
            $request->surl = $surl;
            $request->fs_id = $fs_id;
            $request->randsk = $randsk;
            $request->share_id = $share_id;
            $request->uk = $uk;
            return $next($request);
        }
        return $next($request);
    }

}
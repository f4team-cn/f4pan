<?php

namespace app\middleware;

use app\model\SystemModel;

class ParseMiddleware
{
    public function handle($request, \Closure $next){
        $req_id = $request->request('req_id');
        if(!isset($req_id)){
            return responseJson(-1, "error, 缺少必要参数 req_id");
        }
        $redis = \think\facade\Cache::store('redis');
        if(!$redis->has($req_id)){
            return responseJson(-1, "error, 请先使用密码");
        }
        [$surl, $password] =  explode('|',$redis->get($req_id));
        $dir = $request->request('dir')??'';
        $isRoot = $request->request('isroot')??true;
        $fs_id = $request->request('fs_id')??'';
        $randsk = $request->request('randsk')??'';
        $share_id = $request->request('share_id')??'';
        $uk = $request->request('uk')??'';
        $short = $request->request('short')??false;
        $request->surl = $surl;
        $request->password = $password;
        $request->isroot = $isRoot;
        $request->dir = $dir;
        $request->surl = $surl;
        $request->fs_id = $fs_id;
        $request->randsk = $randsk;
        $request->share_id = $share_id;
        $request->uk = $uk;
        $request->short = $short;
        return $next($request);
    }
}
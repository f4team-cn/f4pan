<?php

namespace app\middleware;

use app\model\VisitModel;

class VisitRecorder
{
    public function handle($request, \Closure $next)
    {
        $ip = $request->ip();
        $route = $request->pathinfo();
        $model = new VisitModel();
        $model->addVisit([
            'ip_address'=>$ip,
            'route'=>$route
        ]);
        return $next($request);
    }
}
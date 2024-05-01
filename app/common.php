<?php
// 应用公共文件

function responseJson(int $code = 200, string $message = '操作成功', mixed $data = []): \think\Response
{
        return \think\Response::create([
                'code' => $code,
                'message' => $message,
                'data' => $data
        ], 'json');
}

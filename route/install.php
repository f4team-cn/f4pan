<?php
use think\facade\Route;
Route::group('api', function () {
    Route::group('install', function (){
        Route::get('check_env', '\\app\\controller\\Install@checkEnv');
        Route::post('test_db', '\\app\\controller\\Install@testDb');
        Route::post('test_redis', '\\app\\controller\\Install@testRedis');
        Route::post('install', '\\app\\controller\\Install@Install');
    });
});
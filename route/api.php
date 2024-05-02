<?php
use think\facade\Route;

Route::group('api', function () {
    Route::group('v1', function () {
        Route::group('parse', function () {
            Route::get('get_file_list', '\\app\\controller\\Parse@getFileList');
            //TODO: apikey
            Route::get('parse_file', '\\app\\controller\\Parse@parseFile');
        });
    });
    //TODO: 管理员系统
    Route::group('admin', function () {
        Route::post('login', '\\app\\controller\\Admin@login');
        Route::post('generate_api_key', '\\app\\controller\\Admin@generateApiKey');
        Route::get('get_api_key', '\\app\\controller\\Admin@getApiKey');
        Route::post('add_svip', '\\app\\controller\\Admin@addSvip');
        Route::post('delete_svip', '\\app\\controller\\Admin@deleteSvip');
        Route::get('get_svip_list', '\\app\\controller\\Admin@getSvipList');
        Route::get('get_parse_key_list', '\\app\\controller\\Public@getParseKeyList');
    });
    //TODO: 公共接口
    Route::group('public', function () {
        Route::get('get_status', '\\app\\controller\\Public@getStatus');
        Route::get('get_notice', '\\app\\controller\\Public@getNotice');
        Route::get('get_parse_key', '\\app\\controller\\Public@getParseKey');
        Route::get('use_parse_key', '\\app\\controller\\Public@UseParseKeyList');
    });
    Route::group('web_api', function () {
        Route::get('get_qrcode', '\\app\\controller\\WebApi@getQrcode');
        Route::get('unicast', '\\app\\controller\\WebApi@unicast');
        Route::get('qrcode_login', '\\app\\controller\\WebApi@qrcodeLogin');
    });
});
Route::miss('\\app\\controller\\Error@index');
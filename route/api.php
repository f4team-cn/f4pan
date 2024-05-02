<?php
use think\facade\Route;

Route::group('api', function () {
    Route::group('v1', function () {
        Route::group('parse', function () {
            Route::post('get_file_list', '\\app\\controller\\Parse@getFileList');
            Route::post('parse_file', '\\app\\controller\\Parse@parseFile');
        });
    });
    Route::group('admin', function () {
        Route::post('login', '\\app\\controller\\Admin@login');
        Route::post('generate_api_key', '\\app\\controller\\Admin@generateApiKey');
        Route::get('get_api_key', '\\app\\controller\\Admin@getApiKey');
        Route::post('add_svip', '\\app\\controller\\Admin@addSvip');
        Route::post('delete_svip', '\\app\\controller\\Admin@deleteSvip');
        Route::get('get_svip_list', '\\app\\controller\\Admin@getSvipList');
        Route::get('get_parse_key_list', '\\app\\controller\\Public@getParseKeyList');
    });
    Route::group('public', function () {
        Route::get('get_status', '\\app\\controller\\Public@getStatus');
        Route::get('get_notice', '\\app\\controller\\Public@getNotice');
        Route::get('get_parse_key', '\\app\\controller\\Public@getParseKey');
        Route::get('use_parse_key', '\\app\\controller\\Public@UseParseKeyList');
    });
});
Route::miss('\\app\\controller\\Error@index');
<?php

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Config;

class Install extends BaseController
{
    public function checkEnv(){
        //php版本要求大于8.0
        $php_version = version_compare(phpversion(), '8.1', '>=');
        $ext_check['php_version'] = phpversion();
        if (!$php_version){
            return responseJson(-1 , 'PHP版本要求大于8.0');
        }
        //检查所需环境
        $ext = [
            'pdo_mysql',
            'redis',
        ];
        foreach ($ext as $v){
            $ext_check['ext'][$v] = extension_loaded($v);
        }
        //如果环境检查有一项不通过则返回错误
        foreach ($ext_check['ext'] as $k=>$v){
            if (!$v){
                return responseJson(-1 , '环境检查未通过', $ext_check);
            }
        }
        return responseJson(1 , '环境检查通过', $ext_check);
    }

    public function testDb(){
        if(!extension_loaded('pdo_mysql')){
            return responseJson(-1 , '请安装mysql扩展');
        }
        $db_config = [
            'hostname' => $this->request->param('hostname')?? '127.0.0.1',
            'database' => $this->request->param('database')?? 'f4pan',
            'username' => $this->request->param('username'),
            'password' => $this->request->param('password'),
            'hostport' => $this->request->param('hostport')?? 3306,
        ];
        //测试链接 pdo
        try{
            $pdo = new \PDO("mysql:host={$db_config['hostname']};port={$db_config['hostport']};}", $db_config['username'], $db_config['password']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['database']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        }catch (\PDOException $e){
            return responseJson(-1 , '数据库链接失败', $e->getMessage());
        }
        return responseJson(1 , '数据库链接成功');
    }

    public function testRedis(){
        if(!extension_loaded('redis')){
            return responseJson(-1 , '请安装redis扩展');
        }
        $redis_config = [
            'host' => $this->request->param('host')?? '127.0.0.1',
            'port' => $this->request->param('port')?? 6379,
            'password' => $this->request->param('password'),
        ];
        //测试链接 redis
        try{
            $redis = new \Redis();
            $redis->connect($redis_config['host'], $redis_config['port']);
           $redis->auth($redis_config['password']);
       }catch (\RedisException $e){
            return responseJson(-1 , 'Redis链接失败');
        }
        return responseJson(1 , 'Redis链接成功');
   }

   public function Install(){
        //检测是否存在安装固定文件
       if (file_exists(app()->getRootPath() . 'install.lock')){
           return responseJson(-1 , '系统已安装，如果要重新安装，请删除根目录下的install.lock文件，请注意，重新安装需要清空数据库数据');
       }
       $data = [
           'admin_password' => $this->request->param('admin_password'),
           'requires_key' => $this->request->param('requires_key') ?? 'dynamic',
           'notice_id' => $this->request->param('notice_id') ?? 0,
           'key_last_time' => $this->request->param('key_last_time') ?? 300,
           'fixed_key' => $this->request->param('fixed_key') ?? '',
           'real_url_last_time' => $this->request->param('real_url_last_time') ?? 1800,
           'parse_ua' => $this->request->param('parse_ua') ?? 'netdisk',
           'normal_cookie' => $this->request->param('normal_cookie') ?? '',
           'is_active' => true
       ];
        $db_config = [
            'hostname' => $this->request->param('db_hostname')?? '127.0.0.1',
            'database' => $this->request->param('db_database')?? 'f4pan',
            'username' => $this->request->param('db_username'),
            'password' => $this->request->param('db_password'),
            'hostport' => $this->request->param('db_hostport')?? 3306,
        ];
       try{
           $pdo = new \PDO("mysql:host={$db_config['hostname']};port={$db_config['hostport']};}", $db_config['username'], $db_config['password']);
           $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['database']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
       }catch (\PDOException $e){
           return responseJson(-1 , '数据库链接失败', $e->getMessage());
       }
       $redis_config = [
           'host' => $this->request->param('redis_host')?? '127.0.0.1',
           'port' => $this->request->param('redis_port')?? 6379,
           'password' => $this->request->param('redis_password'),
       ];
       //缺少参数提示
       if (empty($db_config['username']) || empty($db_config['password']) || empty($data['admin_password'])){
           return responseJson(-1 , '缺少必要参数');
       }
       $db_host = $db_config['hostname'];
       $db_name = $db_config['database'];
       $db_user = $db_config['username'];
       $db_pass = $db_config['password'];
       $db_port = $db_config['hostport'];
       $redis_host = $redis_config['host'];
       $redis_port = $redis_config['port'];
       $redis_pass = $redis_config['password']??'';
       $env = <<<EOF
APP_VERSION = 1.0.0

DEFAULT_LANG = zh-cn

DB_TYPE = mysql
DB_HOST = $db_host
DB_NAME = $db_name
DB_USER = $db_user
DB_PASS = $db_pass
DB_PORT = $db_port
DB_CHARSET = utf8

REDIS_HOST = $redis_host
REDIS_PORT = $redis_port
REDIS_PASS = $redis_pass
EOF;
       file_put_contents(app()->getRootPath().'.env', $env);
       //增加config配置以便于初始化数据库
       $config = Config::get('database');
       $config['connections']['install_db']['type'] = 'mysql';
       $config['connections']['install_db']['hostname'] = $db_config['hostname'];
       $config['connections']['install_db']['database'] = $db_config['database'];
       $config['connections']['install_db']['username'] = $db_config['username'];
       $config['connections']['install_db']['password'] = $db_config['password'];
       $config['connections']['install_db']['hostport'] = $db_config['hostport'];
       $config['connections']['install_db']['charset'] = 'utf8';
       $config['connections']['install_db']['prefix'] = '';
       Config::set($config, 'database');
       $this->initDb($data);
       //创建一个安装固定文件
       file_put_contents(app()->getRootPath().'install.lock', 'installed');
       return responseJson(1 , '安装成功');
   }

   private function initDb($data){
        $sql = file_get_contents(app()->getRootPath() . 'app/database/db.sql');
        $sql_explode = explode(";", $sql);
        Db::connect('install_db')->startTrans();
        foreach ($sql_explode as $sql) {
           if (!empty($sql)) {
               Db::connect('install_db')->execute($sql);
           }
        }
        Db::connect('install_db')->table('system')->save($data);
        Db::connect('install_db')->commit();
   }
}

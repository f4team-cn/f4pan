<?php
/**
 * @description: cURL 工具类
 * @Author     : 莹.
 * @date       : 2023/01/22 - 13:17
 * @project    : 莹莹API
 */

namespace app\utils;

class CurlUtils {
    private static $instance = null;
    private $cookie_jar;
    private $options;
    const ua_android = 'Mozilla/5.0 (Linux; Android 10.0; MHA-AL00 Build/HUAWEIMHA-AL00; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/43.0.2357.134 Mobile Safari/537.36';
    const ua_pc= 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36 Edg/89.0.774.54';
    function __construct () {
        $this->cookie_jar = mb_convert_encoding(__DIR__, "GBK", "UTF-8") . DIRECTORY_SEPARATOR . 'cookie.txt';
        $this->options= [
            CURLOPT_HEADER => true, // 输出响应头
            CURLOPT_TIMEOUT=> 5,// 超时 秒
            CURLOPT_RETURNTRANSFER => true, // 输出数据流
            CURLOPT_FOLLOWLOCATION => true, // 自动跳转追踪
            CURLOPT_AUTOREFERER=> true, // 自动设置来路信息
            CURLOPT_SSL_VERIFYPEER => false,// 认证证书检查
            CURLOPT_SSL_VERIFYHOST => false,// 检查SSL加密算法
            CURLOPT_NOSIGNAL => true, // 忽略所有传递的信号
            CURLOPT_HTTPHEADER => [], // 请求头
            CURLINFO_HEADER_OUT=> true, // 获取请求头
            CURLOPT_ENCODING => 'gzip'
        ];
    }
    public static function __callStatic ($method, $args) {
        if (self::$instance === null)
            self::$instance = new self();
        return self::$instance->$method(...$args);
    }
    public function __call ($method, $args) {
        switch ($method) {
            case 'autoCookie': // Cookie自动化，默认关闭
                if (@$args[0]) {
                    $this->options[CURLOPT_COOKIEFILE] = $this->cookie_jar;
                    $this->options[CURLOPT_COOKIEJAR]= $this->cookie_jar;
                }
                break;
            case 'timeout':
                if (isset($args[0]))
                    $this->options[CURLOPT_TIMEOUT] = $args[0];
                break;
            case 'location': // 自动重定向，默认开启
                if (isset($args[0]))
                    $this->options[CURLOPT_FOLLOWLOCATION] = $args[0];
                break;
            case 'get':
                if (isset($args[0])) {
                    $data = isset($args[1]) ? is_array($args[1]) ? http_build_query($args[1], '', '&', PHP_QUERY_RFC3986) : $args[1] : false;
                    $this->options[CURLOPT_URL] = $data !== false ? $args[0] . (preg_match('/\?/', $args[0]) ? '&' : '?') . $data : $args[0];
                }
                break;
            case 'upload':
                if (isset($args[0])) {
                    $this->options[CURLOPT_URL]= $args[0];
                    $this->options[CURLOPT_POST] = true;
                    if (isset($args[1]))
                        $this->options[CURLOPT_POSTFIELDS] = $args[1];
                }
                break;
            case 'post':
                if (isset($args[0])) {
                    $this->options[CURLOPT_URL]= $args[0];
                    $this->options[CURLOPT_POST] = true;
                    if (isset($args[1]))
                        $this->options[CURLOPT_POSTFIELDS] = is_array($args[1]) ? http_build_query($args[1], '', '&', PHP_QUERY_RFC3986) : $args[1];
                }
                break;
            case 'json':
                if (isset($args[0])) {
                    $this->options[CURLOPT_URL]= $args[0];
                    $this->options[CURLOPT_POST] = true;
                    $this->options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json; charset=utf-8';
                    if (!empty(@$args[1]))
                        $this->options[CURLOPT_POSTFIELDS] = is_array($args[1]) ? json_encode($args[1], 256) : $args[1];
                }
                break;
            case 'ua':
                if (isset($args[0])) {
                    if (in_array(strtolower($args[0]), ['m', 'wap', 'android']))
                        $args[0] = self::ua_android; else if (strtolower($args[0]) === 'pc')
                        $args[0] = self::ua_pc; else if (strtolower($args[0]) === 'audo')
                        $args[0] = @$_SERVER['HTTP_USER_AGENT'];
                    $this->options[CURLOPT_USERAGENT] = $args[0];
                }
                break;
            case 'referer':
                if (isset($args[0]))
                    $this->options[CURLOPT_REFERER] = $args[0] === true && !empty(@$this->options[CURLOPT_URL]) ? $this->options[CURLOPT_URL] : $args[0];
                break;
            case 'header': // 来路
                if (isset($args[0])) {
                    if (is_array($args[0])) {
                        // 数组键值对形式
                        foreach ($args[0] as $k => $v)
                            $this->options[CURLOPT_HTTPHEADER][] = $k . ': ' . $v;
                    } else if (isset($args[1])) {
                        // 双参数键值形式
                        $this->options[CURLOPT_HTTPHEADER][] = $args[0] . ': ' . $args[1];
                    } else {
                        // 单参数形式
                        $arr = preg_match_all('/([^:]*?)\s*:\s*(.*)\s*/', $args[0], $m) > 0 ? array_combine($m[1], $m[2]) : [];
                        foreach ($arr as $k => $v)
                            $this->options[CURLOPT_HTTPHEADER][] = $k . ': ' . $v;
                    }
                }
                break;
            case 'cookie':
                if (isset($args[0])) {
                    if (is_array($args[0])) {
                        // 数组键值对形式
                        $arr = [];
                        foreach ($args[0] as $k => $v)
                            $arr[] = $k . '=' . $v;
                        $cookie = implode('; ', $arr);
                    } else if (isset($args[1])) // 双参数键值形式
                        $cookie = $args[0] . '=' . $args[1] . ';'; else // 单参数形式
                        $cookie = $args[0];
                    $this->options[CURLOPT_COOKIE] = !empty(@$this->options[CURLOPT_COOKIE]) ? $this->options[CURLOPT_COOKIE]
                        . (preg_match('/;\s*$/', $this->options[CURLOPT_COOKIE]) ? '' : '; ') . $cookie : $cookie;
                }
                break;
            case 'ajax':
                if (isset($args[0]) && $args[0] === true)
                    $this->options[CURLOPT_HTTPHEADER][] = 'X-Requested-With: XMLHttpRequest';
                break;
            case 'getCookie': // 读取Cookie文件中的cookie
                $url= isset($args[0]) && filter_var($args[0], FILTER_VALIDATE_URL) ? $args[0] : $this->options[CURLOPT_URL];
                $cookie = @file_get_contents($this->cookie_jar);
                $arr= parse_url($url);
                $host = preg_replace('/.*?\.(?=.+\..+$)/', '', $arr['host']);
                $p= preg_match_all('/' . preg_quote($host, '/') . '(?:\s+([^\s\r\n]+))(?:\s+([^\s\r\n]+))(?:\s+([^\s\r\n]+))(?:\s+([^\s\r\n]+))(?:\s+([^\s\r\n]+))(?:\s+([^\s\r\n]+))(?:\r*\n|$)/', rawurldecode($cookie), $m);
                return $p > 0 ? array_combine($m[5], $m[6]) : [];
            case 'head': // 获取响应头，默认不需要响应体，false表示需要
                $this->options[CURLOPT_HEADER] = true;
                $this->options[CURLOPT_NOBODY] = true;
                return $this->request($method, $args);
            case 'html':
            case 'obj':
            case 'xml':
            case 'text':
            case 'body':
            case 'arrayobject':
            case 'jsonobject':
            case 'response':
            case 'all':
                return $this->request($method, $args);
            case 'down':
                $this->options[CURLOPT_HEADER] = false;
                $this->options[CURLOPT_NOBODY] = false;
                $this->options[CURLOPT_FOLLOWLOCATION] = true;
                return $this->request($method, $args);
            default:
                exit("错误：不支持{$method}方法！");
        }
        return $this;
    }
    //执行请求(get, post)并返回数据
    private function request ($method, $args) {
        $ch = curl_init();
        curl_setopt_array($ch, $this->options);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($method === 'down') {
            if (isset($args[1])) $file = trim($args[0]) . DIRECTORY_SEPARATOR . trim($args[1]); else if (isset($args[0])) $file = trim($args[0]); else $file = './' . pathinfo($this->options[CURLOPT_URL], PATHINFO_BASENAME);
            try {
                $fo = fopen($file, 'a');
                fwrite($fo, $data);
                fclose($fo);
                return true;
            }
            catch (\Exception $e) {
                return $e->getMessage();
            }
        }
        $info['header'] = trim(substr($data, 0, $info['header_size']));
        $info['response'] = substr($data, $info['header_size']);
        $headers = [$info['http_code']];
        $p = preg_match_all('/(?<=^|[\r\n])\s*([^:]+)\s*:\s*(.+?)\s*(?=[\r\n]|$)/', $info['header'], $n);
        $info['headers'] = $p > 0 ? array_merge_recursive($headers, $this->array_combine_recursive($n[1], $n[2])) : [];
        $cookies = isset($info['headers']['Set-Cookie']) ? $info['headers']['Set-Cookie'] : @$info['headers']['set-cookie'];
        $info['cookies'] = !empty($cookies) ? $this->parse_cookie(is_array($cookies) ? $cookies : [$cookies]) : [];
        $info['cookie']= !empty($info['cookies']) ? array_column($info['cookies'], 'value', 'key') : [];
        $info['obj'] = ($obj = json_decode($info['response'])) && is_object($obj) ? $obj : null;
        $result = $info['response'];
        switch ($method) {
            case 'head':
                $result = $info[@$args[0] ? 'header' : 'headers'];
                break;
            case 'getCookie':
                $result = $info['cookie'];
                break;
            case 'text':
            case 'body':
            case 'response':
            case 'html':
                $result = $info['response'];
                if (isset($args[0]))
                    $result = mb_convert_encoding($result, 'UTF-8', $args[0]);
                if ($method === 'html')
                    $result = html_entity_decode($result);
                break;
            case 'obj':
                $result = json_decode($info['response'], @$args[0] === true);
                break;
            case 'jsonobject':
            case 'arrayobject':
                $result = json_decode($info['response'],true);
                break;
            case 'xml':
                $result = simplexml_load_string($info['response'], 'SimpleXMLElement', LIBXML_NOCDATA);
                break;
            case 'all':
                $result = isset($args[0]) ? @$info[$args[0]] : $info;
                break;
        }
        self::$instance = null;
        return $result;
    }
    private function array_combine_recursive ($keys, $values) {
        $arr = [];
        foreach ($keys as $i => $key) {
            $value = isset($values[$i]) ? $values[$i] : null;
            if (!isset($arr[$key]))
                $arr[$key] = $value; else if (is_array($arr[$key]))
                $arr[$key][] = $value; else
                $arr[$key] = [$arr[$key], $value];
        }
        return $arr;
    }
    private function parse_cookie ($header) {
        $parse_cookie_str = function ($str) {
            $p= preg_match_all('/(?<=^|;)\s*([^=]+?)\s*=\s*(.*?)\s*(?=;|$)/', $str, $m);
            $i= 0;
            $cookie = [];
            while ($i < $p) {
                if ($i === 0) {
                    $cookie['key'] = $m[1][$i];
                    $cookie['value'] = $m[2][$i];
                } else if ($m[1][$i] === 'expires') {
                    $d = date_create($m[2][$i], timezone_open("Asia/Shanghai"));
                    $timestamp = $d->getTimestamp();
                    $cookie[$m[1][$i]] = $timestamp;
                } else
                    $cookie[$m[1][$i]] = $m[2][$i];
                $i++;
            }
            return $cookie;
        };
        $cookies = [];
        if (is_array($header)) {
            foreach ($header as $cookie) {
                $cookie = $parse_cookie_str($cookie);
                if (!empty($cookie))
                    $cookies[$cookie['key']] = $cookie;
            }
        } else if (is_string($header)) {
            $p = preg_match_all('/(?<=^|[\r\n])\s*Set-Cookie\s*:\s*(.+?)(?=[\r\n]|$)/i', $header, $m);
            $i = 0;
            while ($i < $p) {
                $cookie = $parse_cookie_str($m[1][$i]);
                if (!empty($cookie))
                    $cookies[$cookie['key']] = $cookie;
                $i++;
            }
        }
        return $cookies;
    }
}
<?php
namespace Fuzqing\WpDevUtils;

use Exception;
use ReflectionException;

/**
 * WordPress开发基础类库
 * Class Util
 * @package Fuzqing\WpDevUtils
 */
class Utils
{
    /**
     * 获取客户端的真实IP：
     * PHP里用来获取客户端IP的变量：
     * $_SERVER['HTTP_CLIENT_IP']这个头是有的，但是很少，不一定服务器都实现了。客户端可以伪造。
     * $_SERVER['HTTP_X_FORWARDED_FOR'] 是有标准定义，用来识别经过HTTP代理后的客户端IP地址，客户端可以伪造，格式：clientip,proxy1,proxy2。
     * 详细解释见 http://zh.wikipedia.org/wiki/X-Forwarded-For
     * $_SERVER['REMOTE_ADDR'] 是可靠的， 它是最后一个跟你的服务器握手的IP，可能是用户的代理服务器，也可能是自己的反向代理。客户端不能伪造。
     * 客户端可以伪造的参数必须过滤和验证！很多人以为$_SERVER变量里的东西都是可信的，其实并不不然
     * $_SERVER['HTTP_CLIENT_IP']和$_SERVER['HTTP_X_FORWARDED_FOR']都来自客户端请求的header里面
     * 所以我们可以可以用$_SERVER['REMOTE_ADDR']，或者使用getenv("REMOTE_ADDR")来获取客户端的真实IP，所谓的真实IP是指最后一个和我们服务器通信的IP
     * @link https://huangweitong.com/249.html
     * @return string
     */
    public static function getRemoteIp(): string
    {
        return isset($_SERVER) ? $_SERVER['REMOTE_ADDR'] : getenv("REMOTE_ADDR");
    }

    /**
     * 获取请求参数（包括GET和POST）
     *
     * @param  $key string 请求参数
     * @param  $default string 默认值 如果没有此请求参数的话就返回默认值
     *
     * @return string
     */
    public static function getRequestParameter(string $key, string $default = ''): string
    {
        // If not request set
        if (!isset($_REQUEST[$key]) || empty($_REQUEST[$key])) {
            return $default;
        }

        // Set so process it
        return trim(strip_tags((string)wp_unslash($_REQUEST[$key])));
    }

    /**
     * 拼接URL参数
     *
     * 把 https://fuzqing.com/?name=fuzqing
     * 追加参数后转换成 https://fuzqing.com/?name=fuzqing&age=18
     *
     * @param string $url URL
     * @param string $key 参数名
     * @param string $val 参数值
     * @return string
     */
    public static function AddUrlParameter(string $url, string $key, string $val): string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!empty($query)) {
            if (strpos($query, $key) === false) {
                $url .= "&$key=$val";
            } else {
                $url = substr($url, 0, -1 * strlen($query));
                $url .= preg_replace('/(.+?)=([^&?]*)/', "$key=$val", $query);
            }
        } else {
            $url .= "?$key=$val";
        }
        return $url;
    }

    /**
     * 根据分隔符把字符串转为驼峰式字符串
     *
     * 下划线风格的字符串：hello_world
     * 分隔符： _
     * 转换结果：helloWorld
     *
     * 先将原字符串转小写，原字符串中的分隔符用空格替换，在字符串开头加上分隔符
     * 将字符串中每个单词的首字母转换为大写，再去空格，去字符串首部附加的分隔符
     * @param string $un_camelize_words
     * @param string $separator
     * @return string
     */
    public static function camelize(string $un_camelize_words, string $separator = '_'): string
    {
        $un_camelize_words = $separator . str_replace($separator, " ", strtolower($un_camelize_words));
        return ltrim(str_replace(" ", "", ucwords($un_camelize_words)), $separator);
    }

    /**
     * 驼峰命名转下划线命名
     *
     * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     * @param $camelize_words string
     * @param string $separator string
     * @return string
     */
    public static function unCamelize(string $camelize_words, string $separator = '_'): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelize_words));
    }

    /**
     * bytes 字节转 'B', 'KB', 'MB', 'GB', 'TB'
     *
     * @param integer $size
     * @return string
     */
    public static function formatBytes(int $size): string
    {
        $units = [' B', ' KB', ' MB', ' GB', ' TB'];

        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }
        return round($size, 2).$units[$i];
    }

    /**
     * 计算两个日期相隔多少年，多少月，多少天
     *
     * @param string $date1 格式如：2020-08-18
     * @param string $date2 格式如：2020-09-01
     * @return array array('年','月','日');
     * @throws Exception
     */
    public static function diffDate(string $date1, string $date2=''): array
    {
        if (empty($date2)) {
            $date2 = date('Y-m-d',time());
        }
        $datetime1 = new \DateTime($date1);
        $datetime2 = new \DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        $time['y'] = $interval->format('%Y');
        $time['m'] = $interval->format('%m');
        $time['d'] = $interval->format('%d');
        $time['h'] = $interval->format('%H');
        $time['i'] = $interval->format('%i');
        $time['s'] = $interval->format('%s');
        // 两个时间相差总天数
        $time['a'] = $interval->format('%a');
        return $time;
    }
    /**
     * 获取WordPress主题名称
     * @return string
     */
    public static function getThemeName(): string
    {
        return wp_get_theme()->get('Name');
    }

    /**
     * 根据URL获取顶级域名
     * @param $url
     * @return string
     */
    public static function getTopDomain($url): string
    {

        $url = "https://".preg_replace('/(http|https):\/\//s','',strtolower($url));
        $hosts = parse_url($url);
        $host = $hosts['host'];
        //查看是几级域名
        $data = explode('.', $host);
        $n = count($data);
        //判断是否是双后缀
        $preg = '/[\w].+\.(com|net|org|gov|edu)\.cn$/';
        if(($n > 2) && preg_match($preg,$host)){
            //双后缀取后3位
            $host = $data[$n-3].'.'.$data[$n-2].'.'.$data[$n-1];
        }else{
            //非双后缀取后两位
            $host = $data[$n-2].'.'.$data[$n-1];
        }
        return $host;
    }


    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    public static function getRandCode(int $length = 20): string
    {
        $auth_code = '';
        //将你想要的字符添加到下面字符串中，默认是数字0-9和26个英文字母
        $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $char_len = strlen($chars);
        for($i=0;$i<$length;$i++){
            $loop = mt_rand(0, ($char_len-1));
            //将这个字符串当作一个数组，随机取出一个字符，并循环拼接成你需要的位数
            $auth_code .= $chars[$loop];
        }
        return $auth_code;
    }

    /**
     * 检测URL或者域名是否正确
     * @param $domain
     * @return array|string
     */
    public static function checkDomain($domain)
    {
        $domain = trim($domain);
        if (empty($domain)) {
            return ['error'=>'请输入正确的域名'];
        }
        $domain = "https://".preg_replace('/(http|https):\/\//s','',strtolower($domain));

        if (filter_var($domain, FILTER_VALIDATE_URL) === FALSE) {
            return ['error'=>'请输入正确的域名'];
        }

        $domain = parse_url($domain)['host'];

        if (checkdnsrr($domain,"A") === FALSE) {
            return ['error'=>'请确保您的域名已经做了DNS解析'];
        }
        return $domain;
    }

    /**
     * 基本变量调整函数
     *
     * @param mixed $var 待打印输出的变量，支持字符串、数组、对象
     * @param boolean $isExit 打印之后，是否终止程序继续运行
     * @throws ReflectionException
     */
    public static function dump(mixed $var, bool $isExit = false)
    {
        $preStyle = 'padding: 10px; background-color: #f2f2f2; border: 1px solid #ddd; border-radius: 5px;';
        echo '<pre style="' . $preStyle . '">';
        if ($var && (is_array($var) || is_object($var))) {
            if (is_array($var)) {
                print_r($var);
                echo '</pre>';
            } else if (is_object($var)) {
                echo (new \Reflectionclass($var));
                echo '</pre>';
            }
        } else {
            var_dump($var);
            echo '</pre>';
        }
        if ($isExit) {
            exit();
        }
    }

    /**
     * 向文件写入内容，通过 lock 防止多个进程同时操作
     *
     * @param string $file 文件完整地址（路径+文件名）
     * @param string $contents 要写入的内容
     * @return boolean true|false
     * @throws Exception
     */
    function writeFile(string $file, string $contents): bool
    {
        if (file_exists($file) && $contents != '') {
            $fp = fopen($file, 'w+');
            if (flock($fp, LOCK_EX)) {
                fwrite($fp, $contents);
                flock($fp, LOCK_UN);
                return true;
            } else {
                return false;
            }
        } else {
            throw new Exception('Invalid file or invalid written contents!');
        }
    }

    /**
     * 通过浏览器直接下载文件
     *
     * @param string $path 文件地址：针对当前服务器环境的相对或绝对地址
     * @param string|null $name 下载后的文件名（包含扩展名）
     * @param boolean $isRemote 是否是远程文件（通过 url 无法获取文件扩展名的必传参数 name）
     * @param string $proxy 代理，适用于需要使用代理才能访问外网资源的情况
     * @throws Exception
     */
    function downloadFile(string $path, string $name = null, bool $isRemote = false, string $proxy = '')
    {

        $fileRelativePath = $path;
        $savedFileName = $name;
        if (!$savedFileName) {
            $file = pathinfo($path);
            if (!empty($file['extension'])) {
                $savedFileName = $file['basename'];
            } else {
                $errMsg = 'Extension get failed, parameter \'name\' is required!';
                throw new Exception($errMsg);
            }
        }

        // 如果是远程文件，先下载到本地
        if ($isRemote) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $path);
            if ($proxy != '') {
                curl_setopt($ch, CURLOPT_PROXY, $proxy);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
            $fileContent = curl_exec($ch);
            curl_close($ch);

            // 写入临时文件
            $fileRelativePath = tempnam(sys_get_temp_dir(), 'DL');
            $fp = @fopen($fileRelativePath, 'w+');
            fwrite($fp, $fileContent);
        }

        // 执行下载
        if (is_file($fileRelativePath)) {
            header('Content-Description: File Transfer');
            header('Content-type: application/octet-stream');
            header('Content-Length:' . filesize($fileRelativePath));
            if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { // for IE
                header('Content-Disposition: attachment; filename="' . rawurlencode($savedFileName) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $savedFileName . '"');
            }
            readfile($fileRelativePath);
            if ($isRemote) {
                unlink($fileRelativePath); // 删除下载远程文件时对应的临时文件
            }
        } else {
            throw new Exception('Invalid file: ' . $fileRelativePath);
        }
    }

    /**
     * 创建多级目录
     *
     * @param string $path 目录路径
     * @param integer $mod 目录权限（windows忽略）
     * @return bool 创建结果
     */
    public static function mkdir(string $path, int $mod = 0777): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, $mod, true);
        }
        return false;
    }

    /**
     * 基于 UTF-8 的字符串截取
     *
     * @param string $str     待截取的字符串
     * @param int $length 截取长度
     * @param int $start      开始下标
     * @param bool $showEllipsis 是否显示省略号
     * @return string
     */
    public static function substr(string $str, int $length, int $start = 0, bool $showEllipsis = false): string
    {
        $strFullLength = 0; // 字符串完整长度
        $finalStr = '';
        if (function_exists('mb_substr') && function_exists('mb_strlen')) {
            $strFullLength = mb_strlen($str, 'utf8');
            $finalStr = mb_substr($str, $start, min($length, $strFullLength), 'utf8');
        } else {
            $arr = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
            $strFullLength = count($arr);
            $finalStr = join('', array_slice($arr, $start, min($length, $strFullLength)));
        }
        if ($showEllipsis && $length < $strFullLength) {
            $finalStr .= '...';
        }
        return $finalStr;
    }

    /**
     * 兼容性的 json_encode，不编码汉字
     *
     * @param array $arr 待编码的信息
     */
    public static function jsonEncode(array $arr)
    {
        return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 生成 uuid（简易版）
     *
     * @param bool $type 取值 true，格式：XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
     * @return string 32 位的字符串 或 带格式的 36 位字符串
     */
    public static function uuid(bool $type = false): string
    {
        $uuid = md5(uniqid(rand(), true));
        if ($type) {
            $str = strtoupper($uuid);
            $uuid = substr($str, 0, 8) . '-' .
                substr($str, 8, 4) . '-' .
                substr($str, 12, 4) . '-' .
                substr($str, 16, 4) . '-' .
                substr($str, 20);
        }
        return $uuid;
    }

    /**
     * 将数据写入 CSV 文件并直接通过浏览器下载
     *
     * @param array $rows 要导出的数据，格式：
     *     [
     *          ['标题1', '标题2', '标题3'],
     *          ['Jerry', 12, '18812341234'],
     *          ['Tom', 18, '16612341234'],
     *          ...
     *      ]
     * @param string|null $filename 指定 csv 文件名，不带扩展名
     * @throws Exception
     */
    public static function exportCsv(array $rows, string $filename = null)
    {
        if ((!empty($rows)) && is_array($rows)) {

            // 指定下载文件格式
            $name = ($filename) ? $filename . ".csv" : "export.csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $name);

            // 写入文件
            $fp = fopen('php://output', 'w');
            fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF))); // add BOM to fix UTF-8 in Excel
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $row = ['Invalid data, array is required.'];
                }
                fputcsv($fp, $row);
            }
        }
        throw new Exception('Invalid parameter type, array is required.');
    }

    /**
     * 生成随机密码串
     *
     * @param integer $length 密码位数，默认 8 位
     * @param integer $type   密码类型
     *      - 0 默认，字母 + 数字；
     *      - 1 字母 + 数字 + 特殊符号
     * @return string 一个按规则生成的随机密码串
     */
    public static function randomPassword(int $length = 8, int $type = 0): string
    {
        $number = range('0', '9');
        $words = array();
        foreach (range('A', 'Z') as $v) {
            if ($v == 'O' || $v == 'I' || $v == 'L') {
                continue;
            }
            $words[] = $v;
            $words[] = strtolower($v);
        }
        $teshu = array();
        if ($type == 1) {
            $teshu = array('!', '@', '#', '$', '%', '^', '*', '+', '=', '-', '&');
        }
        $arr = array_merge($number, $words, $teshu);
        shuffle($arr);
        return substr(str_shuffle(implode('', $arr)), 0, $length);
    }

    /**
     * 加密
     *
     * @param string $str    待加密的字符串
     * @param string $key     自定义密钥串
     * @param int $expiry  密文有效期，时间戳，单位：秒
     * @return string 加密后的字符串
     */
    public static function encrypt(string $str, string $key = '', int $expiry = 0): string
    {
        return self::encryptDecrypt($str, 'ENCODE', $key, $expiry);
    }

    /**
     * 解密
     *
     * @param string $str 待解密的密文字符串
     * @param string $key 加密时使用的密钥串
     * @return string 解密后的字符串
     */
    public static function decrypt(string $str, string $key = ''): string
    {
        return self::encryptDecrypt($str, 'DECODE', $key);
    }

    /**
     * Discuz! 加密/解密函数
     *
     * @param string $string    明文或密文
     * @param string $operation 操作类型：DECODE 解密，不传或其它任意字符表示加密
     * @param string $key       秘钥串
     * @param integer $expiry    密文有效期，时间戳，单位：秒
     */
    private static function encryptDecrypt(string $string, string $operation = 'DECODE', string $key = '', int $expiry = 0): string
    {

        $ckey_length = 4; // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
        // $key = md5($key ? $key : C('AUTH_CODE_KEY')); // 密匙
        $keya = md5(substr($key, 0, 16)); // 密匙a会参与加解密
        $keyb = md5(substr($key, 16, 16)); // 密匙b会用来做数据完整性验证
        $keyc = $ckey_length ? (
        $operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)
        ) : ''; // 密匙c用于变化生成的密文
        $cryptkey = $keya.md5($keya.$keyc); // 参与运算的密匙
        $key_length = strlen($cryptkey);

        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
        // 解密时会通过这个密匙验证数据完整性
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) :
            sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = [];

        // 产生密匙簿
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        // 核心加解密部分
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            // 验证数据有效性，请看未加密明文的格式
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
                substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 遍历目录
     * @param string $dir  待遍历的目录地址
     * @param array  &$res 目录下的文件或子目录，名称以数组键的形式
     * @return array 目录树
     */
    public static function dirs(string $dir, array &$res = []): array
    {
        $excludeList = ['.', '..', '.DS_Store', '.git', '.gitignore', '.svn'];
        if (file_exists($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (in_array($file, $excludeList)) {
                        continue;
                    }
                    if (is_file(realpath($dir) . '/' .$file)) {
                        $res[$file] = null;
                    } else {
                        $res[$file] = self::dirs(realpath($file), $res);
                    }
                }
                closedir($dh);
            }
        }
        ksort($res);
        return $res;
    }

    /**
     * 根据时区获取准确的时间
     * 说明：原 date 函数会根据当前时区变化；gmdate 永远把时区当作 UTC+0
     * @param string $format 时间格式
     * @param integer $timestamp 待处理的时间戳
     * @param integer $zone 时区，默认：东 8 区
     * @return string
     */
    public static function date(string $format, int $timestamp = 0, int $zone = 8): string
    {
        $timestamp = intval($timestamp) > 0 ? intval($timestamp) : time();
        return gmdate($format, $timestamp + $zone * 3600);
    }

    /**
     * @param $host
     * @param $port
     * @param $password
     * @param $db
     * @return \Redis
     */
    public static function getRedisClient($host,$port,$password,$db): \Redis
    {
        return MyRedis::getRedisClient($host,$port,$password,$db);
    }
}
<?php
namespace cocolait\bootstrap\auth;
use think\Cache;
use think\Config;
class Token{
    // 对象实例
    protected static $instance;

    // 默认的设置参数
    protected $options = [
        'day'                   => 15,      // 生成token值得生命周期 默认15天
        'prefix'                => 'cp_',   // token key值的默认前缀
        'heartbeat_time'        => 10,      // 心跳延长时间 默认10分钟 以分钟为单位
        'heartbeat_status'      => false,   // 是否开启 心跳机制规则状态 开启时，过期前30分钟前才进行累加心跳值 默认关闭
        'heartbeat_status_time' => 30,      // 心跳检测时间 默认为分钟单位 状态开启该值生效
        'encrypt_Key'           => 'cp',    // 存储token值 加密key
        'encrypt_Key_time'      => true     // 存储redis token key值 延长生命周期最多只能保持30天
    ];

    // 初始化到redis token 默认存储周期 以天为单位 默认的生命周期为15天
    protected $tokenDayTime;

    // 存储redis token key值的默认前缀
    protected $tokenKeyPrefix;

    // 存储redis token 心跳机制 心跳时间 延长Token的生命周期 以分钟为单位
    protected $tokenKeyHeartbeatTime;

    // 存储redis token 心跳机制规则 过期时间前30分钟才进行延长Token生成周期操作 默认关闭 false
    protected $tokenKeyHeartbeatStatus;

    // 时间 默认为分钟单位 状态开启该值生效
    protected $tokenKeyHeartbeatStatusTime;

    // 存储redis token key值加密值
    protected $tokenEncryptKey;

    // 存储redis token key值 延长生命周期最多只能保持30天
    protected $tokenKeyTime;

    // 初始化 参数
    protected function __construct($options = [])
    {
        if (!defined('THINK_VERSION')) {
            $this->throwException('该扩展只支持ThinkPHP v5.0.16或以上版本');
        } else {
            if (THINK_VERSION < '5.0.16') {
                $this->throwException('该扩展只支持ThinkPHP v5.0.16或以上版本');
            }
        }
        if (!extension_loaded('redis')) {
            $this->throwException("该扩展需要支持redis 检测扩展未加载");
        }
        $config_token_data = Config::get('token');
        $day = isset($config_token_data['day']) ? $config_token_data['day'] : $this->options['day'];
        $prefix = isset($config_token_data['prefix']) ? $config_token_data['prefix'] : $this->options['prefix'];
        $heartbeat_time = isset($config_token_data['heartbeat_time']) ? $config_token_data['heartbeat_time'] : $this->options['heartbeat_time'];
        $heartbeat_status = isset($config_token_data['heartbeat_status']) ? $config_token_data['heartbeat_status'] : $this->options['heartbeat_status'];
        $heartbeat_status_time = isset($config_token_data['heartbeat_status_time']) ? $config_token_data['heartbeat_status_time'] : $this->options['heartbeat_status_time'];
        $encrypt_Key = isset($config_token_data['encrypt_Key']) ? $config_token_data['encrypt_Key'] : $this->options['encrypt_Key'];
        $encrypt_Key_time = isset($config_token_data['encrypt_Key_time']) ? $config_token_data['encrypt_Key_time'] : $this->options['encrypt_Key_time'];

        $this->tokenDayTime = isset($options['day']) ? $options['day'] : $day;
        $this->tokenKeyPrefix = isset($options['prefix']) ? $options['prefix'] : $prefix;
        $this->tokenKeyHeartbeatTime = isset($options['heartbeat_time']) ? $options['heartbeat_time'] : $heartbeat_time;
        $this->tokenKeyHeartbeatStatus = isset($options['heartbeat_status']) ? $options['heartbeat_status'] : $heartbeat_status;
        $this->tokenKeyHeartbeatStatusTime = isset($options['heartbeat_status_time']) ? $options['heartbeat_status_time'] : $heartbeat_status_time;
        $this->tokenEncryptKey = isset($options['encrypt_Key']) ? $options['encrypt_Key'] : $encrypt_Key;
        $this->tokenKeyTime = isset($options['encrypt_Key_time']) ? $options['encrypt_Key_time'] : $encrypt_Key_time;
    }

    /**
     * 外部调用获取实列
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 获取AccessToken值,前端只需要获取一次
     * @param String $user_id
     * @return array
     */
    public function getAccessToken($user_id)
    {
        $token_id = md5($this->tokenKeyPrefix . $user_id);
        $day_time = ($this->tokenDayTime * 86400) + time();
        $insert_data = [
            'timestamp' => $day_time,
            'user_id' => $user_id,
            'date' => date('Y-m-d H:i:s',$day_time)
        ];
        // 加密token值
        $base_encode_key = [
            'token' => $token_id,
            'timestamp' => uniqid()
        ];
        $token = $this->encrypt(json_encode($base_encode_key), $this->tokenEncryptKey);
        $in_time = new \DateTime(date('Y-m-d H:i:s',$day_time));
        $boll = Cache::set($token_id,$insert_data,$in_time);
        if ($boll) {
            return $this->outJson(0,'获取成功',['token' => $token]);
        } else {
            return $this->outJson(2001,'获取token值失败,Redis服务存储数据失败');
        }

    }

    /**
     * 验证Token
     * @param String $token 前端请求头部token值
     * @return array
     */
    public function checkAccessToken($token)
    {
        $check_token_data = $this->decrypt($token, $this->tokenEncryptKey);
        $check_token_data = json_decode($check_token_data,true);
        if ($check_token_data) {
            if (!isset($check_token_data['token'])) return $this->outJson(3000, 'token验证失败');
        }
        // 赋值解密后的token参数值
        $token = $check_token_data['token'];
        $token_data = Cache::get($token);
        if (!$token_data) return $this->outJson(3000, 'token验证失败');
        if (time() > $token_data['timestamp']) {
            return $this->outJson(2000,'token已过期');
        }

        if ($this->tokenKeyHeartbeatStatus == false) {
            return $this->refreshToken($token,$token_data);
        } else {
            $times = $token_data['timestamp'] - ($this->tokenKeyHeartbeatStatusTime * 60);
            if (time() > $times && time() < $token_data['timestamp']) {
                return $this->refreshToken($token,$token_data);
            } else {
                return $this->outJson(0,'验证token成功',$token_data);
            }
        }

    }

    /**
     * 清除token值
     * @param $token
     * @return array
     */
    public function rmAccessToken($token)
    {
        $check_token_data = $this->decrypt($token, $this->tokenEncryptKey);
        $check_token_data = json_decode($check_token_data,true);
        if ($check_token_data) {
            if (!isset($check_token_data['token'])) return $this->outJson(4000, 'token参数值错误');
        }
        // 赋值解密后的token参数值
        $token = $check_token_data['token'];
        $token_data = Cache::get($token);
        if (!$token_data) return $this->outJson(4000, 'token参数值错误');
        $bool = Cache::rm($token);
        if (!$bool) return $this->outJson(4001, 'token清除失败');
        return $this->outJson(0, 'token清除成功');
    }

    /**
     * 心跳机制 刷新Token的生命周期
     * @param $token_id
     * @param $token_data
     * @return array
     */
    protected function refreshToken($token_id, $token_data)
    {
        $timestamp = $token_data['timestamp'];
        if ($this->tokenKeyTime) {
            // 最多缓存到30天 30天后必须重新登录刷新token
            $times = ($timestamp - time()) / 86400;
            if ($times <= 30) {
                $heartbeat_time = $this->tokenKeyHeartbeatTime * 60;
                $day_time = $timestamp + $heartbeat_time;
                $insert_data = [
                    'timestamp' => $day_time,
                    'user_id' => $token_data['user_id'],
                    'date' => date('Y-m-d H:i:s',$day_time)
                ];
                $in_time = new \DateTime(date('Y-m-d H:i:s',$day_time));
                $boll = Cache::set($token_id,$insert_data,$in_time);
                if ($boll) {
                    return $this->outJson(0,'刷新token心跳时间成功',$insert_data);
                } else {
                    return $this->outJson(2001,'Redis存储数据失败');
                }
            } else {
                return $this->outJson(0,'心跳时间已超过30天',$token_data);
            }
        } else {
            // 无限缓存 心跳时间
            $heartbeat_time = $this->tokenKeyHeartbeatTime * 60;
            $day_time = $timestamp + $heartbeat_time;
            $insert_data = [
                'timestamp' => $day_time,
                'user_id' => $token_data['user_id'],
                'date' => date('Y-m-d H:i:s',$day_time)
            ];
            $in_time = new \DateTime(date('Y-m-d H:i:s',$day_time));
            $boll = Cache::set($token_id,$insert_data,$in_time);
            if ($boll) {
                return $this->outJson(0,'刷新token心跳时间成功',$insert_data);
            } else {
                return $this->outJson(2001,'Redis存储数据失败');
            }
        }
    }

    /**
     * 加密算法
     * @param $data
     * @param string $key
     * @param int $expire
     * @return mixed
     */
    protected function encrypt($data, $key = '', $expire = 0) {
        $key  = md5(empty($key) ? '' : $key);
        $data = base64_encode($data);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = sprintf('%010d', $expire ? $expire + time():0);
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
        }
        return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
    }

    /**
     * 解密算法
     * @param $data
     * @param string $key
     * @return string
     */
    protected function decrypt($data, $key = ''){
        $key    = md5(empty($key) ? '' : $key);
        $data   = str_replace(array('-','_'),array('+','/'),$data);
        $mod4   = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        $data   = base64_decode($data);
        $expire = substr($data,0,10);
        $data   = substr($data,10);
        if($expire > 0 && $expire < time()) {
            return '';
        }
        $x      = 0;
        $len    = strlen($data);
        $l      = strlen($key);
        $char   = $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            }else{
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return base64_decode($str);
    }

    /**
     * 输出json数组
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected function outJson($code = 0, $msg = '', $data = [])
    {
        return [
            "code" => (string) $code,
            "msg" =>  $msg,
            "data" => $data
        ];
    }

    /**
     * 抛出异常
     * @param $error
     * @throws \Exception
     */
    protected function throwException($error) {
        throw new \Exception($error);
    }
}
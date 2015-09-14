<?php
/**
 * 缓存类
 *
 * 保存 access_token 等数据，这里只写个框架，具体请自己扩充。
 * @author ChenKang <ck@kchen.cn>
 * @version 1.0
 */

class Cache
{
    /**
     * 前缀
     *
     * @var string
     */
    private $prefix;

    /**
     * 缓存类型，0:不缓存; 1:本地文件; 2:memcache; 3:mysql;
     *
     * @var int
     */
    private $type;

    /**
     * 和缓存有关的配置，以数组形式保存
     *
     * @var array
     */
    private $option;
    

    /**
     * 构造函数
     *
     * @param string $prefix 前缀
     */
    public function __construct($prefix='', $type=0, $option=array())
    {
        $this->prefix = $prefix;
        $this->type   = $type;
        $this->option = $option;
        switch ($this->type) {
            case 2:
                try {
                    $ip   = isset($this->option['ip'])   ? $this->option['ip']   : '127.0.0.1';
                    $port = isset($this->option['port']) ? $this->option['port'] : 11211;
                    $this->mmc = new Memcache;
                    $this->mmc->connect($ip, $port);
                } catch (Exception $e) {
                    $this->type = 0;
                }
                break;
            
            default:
                break;
        }
    }

    /**
     * 获取缓存
     *
     * @param string $key 键
     * @return string 值
     */
    public function get($key)
    {
        switch ($this->type) {
            case 2:
                $value = $this->mmc->get($this->prefix . $key);
                return $value ? $value : '';
            
            default:
                return '';
        }
    }

    /**
     * 设置缓存
     *
     * @param string $key   键
     * @param string $value 值
     * @param int $lifetime 缓存时间
     * @return bool 结果
     */
    public function set($key, $value, $lifetime = 7200)
    {
        switch ($this->type) {
            case 2:
                $result = $this->mmc->set($this->prefix.$key, $value, 0, $lifetime);
                return $result;
            
            default:
                return FALSE;
        }
    }

    /**
     * 删除缓存
     *
     * @param string $key
     * @return bool 结果
     */
    public function forget($key)
    {
        switch ($this->type) {
            case 2:
                return TRUE;
            
            default:
                return TRUE;
        }
    }
}
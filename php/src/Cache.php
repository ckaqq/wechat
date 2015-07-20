<?php
/**
 * 缓存类
 *
 * 保存 access_token 等数据，提供给 Wechat 类使用。
 * 这里共提供三种方式：本地文件、数据库、memchahe，仅供参考
 * @author ChenKang <ck@kchen.cn>
 * @version 1.0
 */

class Cache
{
    /**
     * 构造函数
     *
     * @param string $prefix 前缀
     * @param int $saveType 保存类型：1.文件；2.数据库；3.memcache
     *
     */
    public function __construct($prefix='', $saveType=1)
    {
        $this->prefix   = $prefix;
        $this->saveType = 1;
    }

    public function get($key)
    {
        return false;
    }

    public function set($key, $value, $lifetime = 7200)
    {
        return false;
    }
}
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
     * 构造函数
     *
     * @param string $prefix 前缀
     */
    public function __construct($prefix='')
    {
        $this->prefix   = $prefix;
        $this->saveType = 1;
    }

    /**
     * 获取缓存
     *
     * @param string $key 键
     * @return string 值
     */
    public function get($key)
    {
        return '';
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
        return false;
    }

    /**
     * 删除缓存
     *
     * @param string $key
     * @return bool 结果
     */
    public function forget($key)
    {
        return true;
    }
}
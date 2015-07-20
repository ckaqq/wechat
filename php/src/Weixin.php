<?php
/**
 * 对微信部分功能的开发（包含公众号和企业号）
 *
 * 说明：public方法在未说明的情况下同时兼容公众号和企业号
 * @author ChenKang <ck@kchen.cn>
 * @version 1.0
 */

require_once __DIR__ . '/Cache.php';

class Weixin
{
    /**
     * 应用ID、密钥
     *
     * @var string
     */
    protected $appId,$appSecret;

    /**
     * 消息类型
     *
     * @var int
     */
    protected $wxType;

    /**
     * 缓存类
     *
     * @var Cache
     */
    protected $cache;

    /**
     * AccessToken
     *
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * 构造函数
     *
     * @param string $appID 应用ID
     * @param string $appSecret 应用密钥，可空
     * @param int    $wxType 微信类型，1：公众号；2：企业号
     */
    public function __construct($appID, $appSecret='', $wxType=1)
    {
        $this->appID     = $appID;
        $this->appSecret = $appSecret;
        $this->wxType    = $wxType;
        $this->cache     = new Cache($appId);
    }

    /**
     * 设置缓存对象
     *
     * 估计用不上，备用吧，以防万一(*^__^*) 
     * @param Cache $cache 缓存
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * 获取 AccessToken
     *
     * @return string AccessToken
     */
    public function getAccessToken()
    {
        // 从内存里取
        if (!empty($this->accessToken)) {
            return $this->accessToken;
        }

        // 从缓存里取
        $token = $this->cache->get('accessToken');
        if (!empty($token)) {
            $this->accessToken = $token;
            return $this->accessToken;
        }

        // 从腾讯那里获取
        if (empty($this->appSecret)) return FALSE;
        if ($this->wxType == 1) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appID}&secret={$this->appSecret}";
        } else {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$this->appID}&corpsecret={$this->appSecret}";
        }
        $result = $this->getHttp($url);
        $array = json_decode($result, TRUE);
        if (empty($array) || !isset($array['access_token'])) return FALSE;

        // 设置缓存
        $this->cache->set("access_token", $array['access_token'], $array['expires_in']);
        $this->accessToken = $array['access_token'];

        return $this->accessToken;
    }

    public function getMenu()
    {

    }

    public function setMenu()
    {

    }

    public function removeMenu()
    {
        
    }

    /**
     * 获取网页
     *
     * @param string $url URL
     * @return string 返回内容
     */
    private function getHttp($url, $post='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if(!empty($post)){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
}




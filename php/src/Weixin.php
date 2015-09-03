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
    protected $appId, $appSecret;

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
     * @param int    $agentID 企业号应用ID，公众号请勿填
     */
    public function __construct($appID, $appSecret='', $agentID=-1)
    {
        $this->appID     = $appID;
        $this->appSecret = $appSecret;
        if ($agentID == -1 || $agentID == "AGENTID") {
            $this->wxType = 1;
        } else {
            $this->wxType = 2;
            $this->agentID = $agentID;
        }
        $this->cache     = new Cache($appID);
    }

    /**
     * 设置缓存对象
     *
     * @param Cache $cache 缓存
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * 获取 AccessToken
     *
     * @return string
     */
    public function getAccessToken()
    {
        // 从内存里取
        if (!empty($this->accessToken)) {
            return $this->accessToken;
        }

        // 从缓存里取
        $token = $this->cache->get('access_token');
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

    /**
     * 获取自定义菜单
     *
     * @return array
     */
    public function getMenu()
    {
        $token = $this->getAccessToken();
        if (empty($token)) return FALSE;

        if ($this->wxType == 1) {
            $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token={$token}";
        } else {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/menu/get?access_token={$token}&agentid={$this->agentID}";
        }
        return json_decode($this->getHttp($url, $menu), TRUE);
    }

    /**
     * 获取设置菜单
     *
     * @param  array $menu 格式参考官方文档
     * @return array 参考微信官方的返回结果
     */
    public function setMenu($menu)
    {
        $token = $this->getAccessToken();
        if (empty($token)) return FALSE;

        if ($this->wxType == 1) {
            $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$token}";
        } else {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/menu/create?access_token={$token}&agentid={$this->agentID}";
        }
        return json_decode($this->getHttp($url, json_encode($menu, JSON_UNESCAPED_UNICODE)), TRUE);
    }

    /**
     * 获取移除菜单
     *
     * @return array 参考微信官方的返回结果
     */
    public function removeMenu()
    {
        $token = $this->getAccessToken();
        if (empty($token)) return FALSE;

        if ($this->wxType == 1) {
            $url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token={$token}";
        } else {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/menu/delete?access_token={$token}&agentid={$this->agentID}";
        }
        return json_decode($this->getHttp($url), TRUE);
    }
    
    /**
     * 通过OpenID获取用户基本信息
     *
     * @param string $id 公众号是openID，企业号是userID
     * @return array 参考微信官方的返回结果
     */
    public function getUserInfo($id)
    {
        $token = $this->getAccessToken();
        if (empty($token)) return FALSE;
        if ($this->wxType == 1) {
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token}&openid={$id}&lang=zh_CN";
        } else {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token={$token}&userid={$id}";
        }
        return json_decode($this->getHttp($url), TRUE);
    }

    /**
     * 主动发消息
     *
     * @param array $data 消息体，参考官方文档
     * @return array 参考微信官方的返回结果
     */
    public function sendMsg($data)
    {
        $token = $this->getAccessToken();
        if (empty($token)) return FALSE;

        if ($this->wxType == 1) {
            $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$token}";
        } else {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$token}";
        }
        return json_decode($this->getHttp($url, json_encode($data, JSON_UNESCAPED_UNICODE)), TRUE);
    }

    /**
     * 获取OAuth的网址
     *
     * @param string $redirect_url 授权后重定向的回调链接地址
     * @param string $state 重定向后会带上的state参数
     * @return string OAut地址
     */
    public function getOauthUrl($redirect_url, $state="")
    {
        $redirect_url = urlencode($redirect_url);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appID}&redirect_uri={$redirect_url}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";
        return $url;
    }

    /**
     * 获取OAuth的结果
     *
     * 用户同意授权后，企业号通过code获取到用户USERID，公众号通过code换取网页授权access_token
     * @param string $code 通过授权获取到的code
     * @return array 参考微信官方的返回结果
     */
    public function getOauthResult($code)
    {
        $token = $this->getAccessToken();
        if (empty($token)) return FALSE;
        if ($this->wxType == 1) {
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appID}&secret={$this->appSecret}&code={$code}&grant_type=authorization_code";
        } else {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$token}&code={$code}";
        }
        $result = $this->getHttp($url);
        $json = json_decode($result, TRUE);
        // 缓存oauth_token
        if ($this->wxType == 1 && $json['access_token']) {
            $this->cache->set('oauth_token', $json['access_token'], $json['expires_in']);
        }
        return $json;
    }

    /**
     * 刷新oauth_token（如果需要）
     *
     * 仅限公众号
     * @param string $oauth_token oauth_token，如果为空则从缓存中取
     * @return boolean 结果
     */
    public function refreshOauthToken($oauth_token = '')
    {
        if (!$oauth_token) $oauth_token = $this->cache->get('oauth_token');
        if (!$oauth_token) return FALSE;
        $url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid={$this->appID}&grant_type=refresh_token&refresh_token={$oauth_token}";
        $array = json_decode($this->getHttp($url), TRUE);
        if ($array['access_token'] && $array['expires_in']) {
            $this->cache->set('oauth_token', $array['access_token'], $array['expires_in']);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 验证oauth_token是否有效
     *
     * 仅限公众号
     * @param string $openid 用户ID
     * @param string $oauth_token 网页授权token，如果为空则从缓存中取
     * @return array 参考微信官方的返回结果
     */
    public function varifyOauthToken($openid, $oauth_token = '')
    {
        if (!$oauth_token) $oauth_token = $this->cache->get('oauth_token');
        if (!$oauth_token) return FALSE;
        $url = "https://api.weixin.qq.com/sns/auth?access_token={$oauth_token}&openid={$openid}";
        return json_decode($this->getHttp($url), TRUE);
    }

    /**
     * 拉取用户信息
     *
     * 仅限公众号，企业号请通过getUserInfo获取用户信息
     * @param string $openid 用户ID
     * @param string $oauth_token 网页授权token，如果为空则从缓存中取
     * @return array 参考微信官方的返回结果
     */
    public function getOauthInfo($openid, $oauth_token="")
    {
        if (!$oauth_token) $oauth_token = $this->cache->get('oauth_token');
        if (!$oauth_token) return FALSE;
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$oauth_token}&openid={$openid}&lang=zh_CN";
        return json_decode($this->getHttp($url), TRUE);
    }

    /**
     * 上传其他类型永久素材
     *
     * 注：图文尚未测试，暂不支持临时素材
     * @param string $type 素材类型，有图文（news）、图片（image）、语音（voice）、视频（video）和缩略图（thumb）
     * @param string $data 上传的素材内容
     * @param boolean $time 素材是否未永久报酬，默认为永久
     * @return array 参考微信官方的返回结果
     */
    public function upload($type, $data, $time=TRUE)
    {
        $token = $this->getAccessToken();
        if (empty($token)) return FALSE;
        // 永久素材
        if ($time == TRUE) {
            if ($this->wxType == 1) {
                if ($type == 'news') {
                    $url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token={$token}";
                    $post = $data;
                } else {
                    $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$token}&type={$type}";
                    $post['media'] = '@'.$data;
                }
            } else {
                if ($type == 'news') {
                    $url = "https://qyapi.weixin.qq.com/cgi-bin/material/add_mpnews?access_token={$token}";
                    $post = $data;
                } else {
                    $url = "https://qyapi.weixin.qq.com/cgi-bin/material/add_material?agentid={$this->agentID}&type={$type}&access_token={$token}";
                    $post['media'] = '@'.$data;
                }
            }
        // 临时素材，等待完成
        } else {
        }
        $result = $this->getHttp($url, $post);
        return json_decode($result, TRUE);
    }

    /**
     * 发送模板消息
     *
     * 仅限公众号
     * @param array $data 消息体，参考官方文档
     * @return array 参考微信官方的返回结果
     */
    public function sendTemplate($data)
    {
        $token = $this->getAccessToken();
        if (empty($token)) return FALSE;

        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$token}";
        $post = json_encode($data, JSON_UNESCAPED_UNICODE);

        $result = $this->getHttp($url, $post);
        return json_decode($result, TRUE);
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

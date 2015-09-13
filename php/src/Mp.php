<?php
/**
 * 模拟登陆微信公众号后台
 *
 * @author ChenKang <ck@kchen.cn>
 * @version 1.0
 */
require_once __DIR__ . '/Cache.php';

class Mp
{
    /**
     * 登陆后获取到的 cookies 和 token
     *
     * @var string
     */
    private $cookie_str, $token;

    public $msg;

    /**
     * 初始化
     *
     * @param string $account 账号
     * @param string $passwd  密码
     */
    public function __construct($account, $passwd)
    {
        $this->cache = new Cache($account);
        // 读缓存
        $this->token = $this->cache->get('token');
        $this->cookie_str = $this->cache->get('cookie_str');
        if (!empty($this->token) && !empty($this->cookie_str)) {
            if ($this->getMsg()) {
                return;
            }
        }
        $this->login($account, $passwd);
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
     * 获取未读消息、新增用户数、用户总数
     *
     * @return array
     */
    public function getMsg()
    {
        $url = "https://mp.weixin.qq.com/cgi-bin/home?t=home/index&lang=zh_CN&token=" . $this->token;
        $html = $this->curl($url);
        if(strstr($html, "<h2>登录超时，请重新")) {
            return "";
        }
        preg_match_all('/<em class="number">(.*?)<\/em>/', $html, $match);
        $this->msg = $match[1];
        return $match[1];
    }

    /**
     * 登陆
     *
     * @param string $account 账号
     * @param string $passwd  密码
     */
    public function login($account, $passwd)
    {
        // 尝试登陆
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://mp.weixin.qq.com/cgi-bin/login");
        curl_setopt($ch, CURLOPT_REFERER, "https://mp.weixin.qq.com/cgi-bin/loginpage?t=wxm2-login&lang=zh_CN");
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "username={$account}&pwd=".md5($passwd)."&imgcode=&f=json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $content = curl_exec($ch);
        curl_close($ch);

        // 分离信息头并判断是否登陆成功
        list($header, $body) = explode("\r\n\r\n", $content, 2);
        $array = json_decode($body, TRUE);
        if($array["base_resp"]["err_msg"] != "ok") {
            return FALSE;
        }

        // 正则 cookies 和 token
        preg_match_all('/&token=(.*?)$/', $array["redirect_url"], $match);
        $this->token = $match[1][0];
        $this->cache->set('token', $this->token);
        preg_match_all("/set\-cookie: ([^;]*)/i", $header, $match);
        $this->cookie_str = implode(';', $match[1]);
        $this->cache->set('cookie_str', $this->cookie_str);

        // 获取信息以验证是否真正登陆成功
        $this->msg = $this->getMsg();
        return !empty($this->msg);
    }

    /**
     * 获取网页
     *
     * @param string $url
     * @param string $post 不填或为空则以GET形式访问
     * @param string $referer 有些页面必须
     * @return string 返回内容
     */
    public function curl($url, $post="", $referer="")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        if($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookie_str);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
}
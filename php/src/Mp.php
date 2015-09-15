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

    /**
     * 未读消息、新增用户数、用户总数
     *
     * @var array
     */
    public $info;

    /**
     * 初始化
     *
     * @param string $account MP账号
     * @param string $passwd  MP密码
     * @param string $cacheType  缓存类型
     * @param string $cacheOption  缓存配置
     */
    public function __construct($account, $passwd, $cacheType=0, $cacheOption=array())
    {
        $this->cache = new Cache($account, $cacheType, $cacheOption);
        // 读缓存
        $this->token = $this->cache->get('token');
        $this->cookie_str = $this->cache->get('cookie_str');
        if (!empty($this->token) && !empty($this->cookie_str)) {
            if ($this->getInfo()) {
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
     * 设置分组
     *
     * @param string $fakeid 用户id
     * @param string $group 分组id
     * @return boolean 结果
     */
    public function setGroup($fakeid, $group)
    {
        $url = "https://mp.weixin.qq.com/cgi-bin/modifycontacts?action=modifycontacts&t=ajax-putinto-group";
        $post = "token={$this->token}&lang=zh_CN&f=json&ajax=1&random=0.".rand(100000000,999999999)."&scene=2&contacttype={$group}&tofakeidlist=".$fakeid;
        $referer = "https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pageidx=0&type=0&token={$this->token}&lang=zh_CN";
        $html = $this->curl($url, $post, $referer);
        $array = json_decode($html, TRUE);
        return $array['base_resp']['err_msg'] == 'ok';
    }

    /**
     * 获取分组列表
     *
     * @return array
     */
    public function getGroupList()
    {
        $url = "https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize=20&pageidx=0&type=0&token={$this->token}&lang=zh_CN";
        $html = $this->curl($url);
        $pattern = '/groups":(.*?)\}\)\.groups/';
        preg_match_all($pattern, $html, $match);
        $array = json_decode($match[1][0], TRUE);
        $result = array();
        foreach ($array as $group) {
            $id = $group['id'];
            unset($group['id']);
            $result[$id] = $group;
        }
        return $result;
    }

    /**
     * 获取用户信息
     *
     * @param string $fakeid 用户id
     * @param string $content 内容
     * @return boolean
     */
    public function sendMsg($fakeid, $content)
    {
        $url = "https://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response&f=json&token=1{$this->token}&lang=zh_CN";
        $post="token={$this->token}&lang=zh_CN&f=json&ajax=1&random=0.".rand(100000000,999999999)."&type=1&content=".urlencode($content)."&tofakeid={$fakeid}&imgcode=";
        $referer = "https://mp.weixin.qq.com/cgi-bin/singlesendpage";
        $html = $this->curl($url, $post, $referer);
        $array = json_decode($html, TRUE);
        return $array["base_resp"]["ret"] == "0";
    }

    /**
     * 获取消息列表
     *
     * @param int $num 消息数量
     * @return int
     */
    public function getMessageList($num=-1)
    {
        $result = array();
        if ($num < 0) {
            $num = $this->info[0];
        }
        
        // 获取最新的消息id
        $latest_msg_id = $this->getLatestMsgId();
        
        // 分批次获取消息内容
        for ($i=0; $i < $num; $i+=50) { 
            $html = $this->curl("https://mp.weixin.qq.com/cgi-bin/message?t=message/list&action=&keyword=&frommsgid={$latest_msg_id}&offset={$i}&count=50&day=7&filterivrmsg=&token={$this->token}&lang=zh_CN");
            $pattern = '/"msg_item":(.*?)\}\)\.msg_item,/';
            preg_match_all($pattern, $html, $match);
            $array = json_decode($match[1][0], TRUE);
            $result = array_merge($result, $array);
        }

        // 多余的删掉
        while(count($result) > $num)
            array_pop($result);
        return $result;
    }

    /**
     * 获取最新的消息id
     *
     * @return int
     */
    public function getLatestMsgId()
    {
        $url = "https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=20&day=7&token={$this->token}&lang=zh_CN";
        $html = $this->curl($url);
        $pattern = "/latest_msg_id : '(.*?)'/";
        preg_match_all($pattern, $html, $match);
        return $match[1][0];
    }

    /**
     * 获取用户信息
     *
     * @param string $fakeid 用户id
     * @return array
     */
    public function getUserInfo($fakeid)
    {
        $url = "https://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&fakeid=".$fakeid;
        $post = "token={$this->token}&lang=zh_CN&f=json&ajax=1&random=".rand(100000000,999999999);
        $referer = "https://mp.weixin.qq.com/cgi-bin/contactmanage";
        $html = $this->curl($url ,$post, $referer);
        $array = json_decode($html, TRUE);
        return isset($array["contact_info"]) ? $array["contact_info"] : array();
    }

    /**
     * 获取头像
     *
     * @param string $fakeid 用户id
     * @return jpg
     */
    public function getHeadImg($fakeid)
    {
        $url = "https://mp.weixin.qq.com/misc/getheadimg?fakeid={$fakeid}&token={$this->token}&lang=zh_CN";
        $referer = "https://mp.weixin.qq.com/cgi-bin/contactmanage";
        return $this->curl($url, "", $referer);
    }

    /**
     * 获取openid，失败则返回空字符串
     *
     * @param string $fakeid 用户id
     * @return string
     */
    public function getOpenid($fakeid)
    {
        $array = $this->getChatLog($fakeid);
        for ($i=0; $i < count($array); $i++) { 
            if ($array[$i]['to_uin'] == $fakeid) {
                $pattern = '/&lt;a href=&quot;#(.*?)&quot;&gt; &lt;\/a&gt;$/';
                if (preg_match($pattern, $array[$i]['content'], $match)) {
                    return $match[1];
                }
            }
        }
        return '';
    }

    /**
     * 获取聊天记录
     *
     * @param string $fakeid 用户id
     * @return array
     */
    public function getChatLog($fakeid)
    {
        $url = "https://mp.weixin.qq.com/cgi-bin/singlesendpage?tofakeid={$fakeid}&t=message/send&action=index&token={$this->token}&lang=zh_CN";
        $html = $this->curl($url);
        $pattern = '/"msg_item":(.*?)\}\};/';
        preg_match_all($pattern, stripslashes($html), $match);
        $array = json_decode($match[1][0], TRUE);
        return $array;
    }

    /**
     * 获取用户列表
     *
     * @param int $group 分组编号，-1则获取所有用户组
     * @param int $num 用户数量，-1则获取分组的所有用户
     * @return array
     */
    public function getUserList($group=-1, $num=-1)
    {
        $result = array();
        if ($group == -1 && $num == -1) {
            $num = $this->info[1];
        } else if ($num == -1) {
            $groups = $this->getGroupList();
            $num = $groups[$group]['cnt'];
        }

        // 计算抓取的次数和每次抓取的个数
        $size = $num;
        $cnt = 1;
        while ($size > 5000) {
            $size /= 2;
            $cnt *= 2;
        }
        $size = ceil($size);

        // 分批次抓取
        for ($i=0; $i < $cnt; $i++) { 
            $url = "https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize={$size}&pageidx={$i}&type=0&token={$this->token}&lang=zh_CN" . ($group==-1 ? '' : '&groupid='.$group);
            $html = $this->curl($url);
            preg_match_all('/"contacts":(.*?)\}\)\.contacts,/', $html, $match);
            $array = json_decode($match[1][0], TRUE);
            $result = array_merge($result, $array);
        }

        // 多余的删掉
        while(count($result) > $num)
            array_pop($result);
        return $result;
    }

    /**
     * 获取未读消息、新增用户数、用户总数
     *
     * @return array
     */
    public function getInfo()
    {
        $url = "https://mp.weixin.qq.com/cgi-bin/home?t=home/index&lang=zh_CN&token=" . $this->token;
        $html = $this->curl($url);
        if(strstr($html, "<h2>登录超时，请重新")) {
            return "";
        }
        preg_match_all('/<em class="number">(.*?)<\/em>/', $html, $match);
        $this->info = $match[1];
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
        $this->info = $this->getInfo();
        return !empty($this->info);
    }

    /**
     * 获取网页
     *
     * @param string $url
     * @param string $post 不填或为空则以GET形式访问
     * @param string $referer 有些页面必须
     * @return string 返回内容
     */
    private function curl($url, $post="", $referer="")
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
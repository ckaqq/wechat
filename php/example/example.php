<?php

require_once __DIR__ . '/../src/Wechat.php';
require_once __DIR__ . '/../src/Weixin.php';
require_once __DIR__ . '/config.php';

class MyWechat extends Wechat
{
    // 构造函数
    public function __construct($token, $aeskey, $appid, $debug=FALSE, $srcret='', $agentID=-1)
    {
        parent::__construct($token, $aeskey, $appid, $debug);
        $this->weixin = new Weixin($appid, $srcret, $agentID);
    }

    // 关注测试
    public function respon_event_subscribe()
    {
        $this->echoText("你来啦");
    }
    
    // 文本消息测试
    public function respon_text()
    {
        $this->echoText("你刚才说的是: " . $this->request['content']);
    }

    // 点击自定义菜单测试
    public function respon_event_click()
    {
        $this->echoText("点我干嘛？");
    }
}

$wechat = new MyWechat(TOKEN, ENCODINGAESKEY, APPID, DEBUG, SECRET, AGENTID);
$wechat->run();

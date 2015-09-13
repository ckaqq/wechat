<?php
/**
 * 微信公众号、企业号模板
 *
 * 说明：基于当前版本的微信公众号和企业号
 * @author ChenKang <ck@kchen.cn>
 * @version 1.0
 */
namespace Wechat;

require_once __DIR__ . '/src/Wechat.php';

class Wechat extends \Wechat
{
    // 构造函数
    public function __construct($token, $aeskey, $appid, $debug=FALSE, $srcret='', $agentID=-1)
    {
        parent::__construct($token, $aeskey, $appid, $debug);
        $this->weixin = new TpWeixin($appid, $srcret, $agentID);
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
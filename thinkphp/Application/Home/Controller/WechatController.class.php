<?php
namespace Home\Controller;
use Think\Controller;
class WechatController extends Controller {
    public function index() {
        $token   = C('TOKEN');
        $aeskey  = C('ENCODINGAESKEY');
        $appid   = C('APPID');
        $debug   = C('DEBUG');
        $srcret  = C('SECRET');
        $agentID = C('AGENTID');
        $wechat  = new \Wechat\TpWechat($token, $aeskey, $appid, $debug, $srcret, $agentID);
        $wechat->run();
    }
}


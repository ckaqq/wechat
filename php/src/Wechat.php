<?php
/**
 * 微信公众号、订阅号模板
 *
 * @author ChenKang <ck@kchen.cn>
 */

require_once __DIR__ . '/MsgCrypt.php';

class Wechat
{

    protected $templateText        = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
    protected $templateNewsBegin   = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>";
    protected $templateNewsContent = "<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>";
    protected $templateNewsEnd     = "</Articles></xml>";
    /**
     * 初始化，判断此次请求是否为验证请求，并以数组形式保存
     *
     * @param string $token 验证信息
     * @param boolean $debug 调试模式，默认为关闭
     */
    public function __construct($token, $aeskey, $appid, $debug = FALSE) {
        //$_GET = json_decode('{"msg_signature":"e8e4f0b5384e47a1fa68bba3f75f09d11f5e8061","timestamp":"1436333042","nonce":"838760432","echostr":"ojtEf3CdFqktmu1XK9ZyazqSIMDJHFqidk6QjRcPpj2euONAU\/0F2xLpKsU\/e8uL1Kw7VCHzzkNtn\/sdRxkpzw=="}', true);

        $this->debug = $debug;

        // 获取请求参数
        $this->timestamp     = $_GET['timestamp'];
        $this->nonce         = $_GET['nonce'];
        if (!$this->debug && (empty($this->timestamp) || empty($this->nonce))) {
            exit("请通过微信访问");
        }

        // 判断消息是否加密
        if (isset($_GET['msg_signature'])) {
            $this->msg_signature = $_GET['msg_signature'];
            $this->encrypted = TURE;
        } else {
            $this->encrypted = FALSE;
        }

        // 判断公众号类型
        if (isset($_GET['signature'])) {
            $wechatType = 1;
            $signature = $_GET['signature'];
        } else {
            $wechatType = 2;
        }

        // 其他参数
        $echostr     = isset($_GET['echostr']) ? $_GET['echostr'] : '';
        $requestData = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");

        // 加解密及验证对象
        $this->msgCryptor = new MsgCrypt($token, $aeskey, $appid);

        if ($this->debug==FALSE) {
            $validResult = TRUE;
            if ($wechatType == 1) {
                $validResult = $this->msgCryptor->mpVerifySig($signature, $this->timestamp, $this->nonce);
            } else if (!empty($echostr)) {
                $validResult = $this->msgCryptor->qyVerifySig($this->msg_signature, $this->timestamp, $this->nonce, $echostr);
            }
            if (!$validResult) exit("ERR: 40001\n\n");
        }

        if($wechatType == 2 && !empty($echostr)) {
            $echostr = $this->msgCryptor->qyEchoStr($echostr);
        }

        // 如果是验证URL则直接输出
        if (!empty($echostr)) exit($echostr);

        if (empty($requestData)) exit('缺少数据');

        // 设置错误处理函数，将错误通过文本消息回复显示
        set_error_handler(array(&$this, 'errorHandler'));

        $this->savePostData($requestData);

    }

    /**
     * 解密并保存微信post的数据，保存至$this->request中
     *
     * @param string $requestData 微信post的数据
     * @return void
     */
    private function savePostData($requestData) {
        
        if ($this->encrypted) {
            
            $xml = '';
            $errCode = $this->msgCryptor->decryptMsg($this->msg_signature, $this->timestamp, $this->nonce, $requestData, $xml);
            
            if ($errCode != 0) exit($errCode);

        } else {

            $xml = $requestData;

        }

        libxml_disable_entity_loader(true);
        $reqArray = (array) simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (empty($reqArray)) exit("xml解析失败");

        // 将数组键名转换为小写，提高健壮性，减少因大小写不同而出现的问题
        $this->request = array_change_key_case($reqArray, CASE_LOWER);

    }


    /**
      * 输出消息
      * @param string $msg 要输出的内容
      * @return void
      */
    private function echoMsg($msg)
    {
        if ($this->encrypted) {
            $sEncryptMsg = "";
            // 加密
            $errCode = $this->msgCryptor->EncryptMsg($msg, $this->timestamp, $this->nonce, $sEncryptMsg);
            if ($errCode != 0) {
                exit ($errCode);
            }
        } else {
            $sEncryptMsg = $msg;
        }
        exit($sEncryptMsg);
    }

    public function echoText($text)
    {
        $resultStr = sprintf($this->templateText, $this->request['fromusername'], $this->request['tousername'], time(), $text);
        $this->echoMsg($resultStr);
    }

    public function echoNews($items)
    {
        $resultStr = sprintf($this->templateNewsBegin, $this->request['fromusername'], $this->request['tousername'], time(), count($item));
        for ($i=0; $i<count($items); $i++) {
            $resultStr .=  sprintf($this->templateNewsContent, $items[$i][0], $items[$i][1], $items[$i][2], $items[$i][3]);
        }
        $resultStr .=  $this->templateNewsEnd;
        $this->echoMsg($resultStr);
    }

    public function run()
    {
        eval('$this->respon_' . $this->request['msgtype'] . '();');
    }

    // 文本消息
    public function respon_text() {}

    // 图片消息
    public function respon_image() {}

    // 语音消息
    public function respon_voice() {}

    // 视频消息
    public function respon_video() {}

    // 小视频消息
    public function respon_shortvideo(){}

    // 小视频消息
    public function respon_link(){}

    // 位置消息
    public function respon_location() {}

    // 事件消息
    public function respon_event()
    {
        eval('$this->respon_event_' . strtolower($this->request['event']) . '();');
    }

    // 订阅事件（公众号、企业号）
    public function respon_event_subscribe() {}

    // 取消订阅（公众号、企业号）
    public function respon_event_unsubscribe() {}

    // 点击自定义菜单（公众号、企业号）
    public function respon_event_click() {}

    // 进入自定义菜单网址（公众号、企业号）
    public function respon_event_view() {}

    // 自动上报地理位置（公众号、企业号）
    public function respon_event_location() {}

    // 扫描带参数二维码事件（公众号）
    public function respon_event_scan() {}

    // 扫码推事件的事件推送（企业号、公众号）
    public function respon_event_scancode_push() {}

    // 扫码推事件且弹出“消息接收中”（企业号、公众号）
    public function respon_event_scancode_waitmsg() {}

    // 弹出系统拍照发图（企业号、公众号）
    public function respon_event_pic_sysphoto() {}

    // 弹出拍照或者相册发图（企业号、公众号）
    public function respon_event_pic_photo_or_album() {}

    // 弹出微信相册发图器（企业号、公众号）
    public function respon_event_pic_weixin() {}

    // 弹出地理位置选择器（企业号、公众号）
    public function respon_event_location_select() {}

    // 成员进入应用（企业号）
    public function respon_event_enter_agent() {}

    // 异步任务完成（企业号）
    public function respon_event_batch_job_result() {}

    /**
     * 自定义的错误处理函数，将 PHP 错误通过文本消息回复显示
     * @param  int $level   错误代码
     * @param  string $msg  错误内容
     * @param  string $file 产生错误的文件
     * @param  int $line    产生错误的行数
     * @return void
     */
    public function errorHandler($level, $msg, $file, $line) {
        if (!$this->debug) {
            return;
        }

        $error_type = array(
            // E_ERROR             => 'Error',
            E_WARNING           => 'Warning',
            // E_PARSE             => 'Parse Error',
            E_NOTICE            => 'Notice',
            // E_CORE_ERROR        => 'Core Error',
            // E_CORE_WARNING      => 'Core Warning',
            // E_COMPILE_ERROR     => 'Compile Error',
            // E_COMPILE_WARNING   => 'Compile Warning',
            E_USER_ERROR        => 'User Error',
            E_USER_WARNING      => 'User Warning',
            E_USER_NOTICE       => 'User Notice',
            E_STRICT            => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED        => 'Deprecated',
            E_USER_DEPRECATED   => 'User Deprecated',
        );

        $template = "PHP 报错啦！\r\n\r\n%s: %s\r\nFile: %s\r\nLine: %s";

        $content = sprintf($template, $error_type[$level], $msg, $file, $line);

        $this->echoText($content);
    }
    /**
     * 处理函数调用异常
     * @param  function $function_name   函数名
     * @param  string $args  参数列表
     * @return void
     */
    public function __call($function_name, $args) {
        if (!$this->debug) {
            return;
        }
        $template = "PHP 报错啦！\r\n\r\n函数%s不存在\r\n参数列表为：%s";

        $content = sprintf($template, $function_name, implode($args));

        $this->echoText($content);
    }
}

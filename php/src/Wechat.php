<?php
/**
 * 微信公众号、企业号模板
 *
 * 说明：基于当前版本的微信公众号和企业号
 * @author ChenKang <ck@kchen.cn>
 * @version 1.0
 */

require_once __DIR__ . '/MsgCrypt.php';

class Wechat
{
    /**
     * xml模板字符串
     *
     * @var string
     */
    protected $templateText        = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
    protected $templateNewsBegin   = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>";
    protected $templateNewsContent = "<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>";
    protected $templateNewsEnd     = "</Articles></xml>";

    /**
     * 调试模式，将错误通过文本消息回复显示
     *
     * @var boolean
     */
    private $debug;

    /**
     * 以数组的形式保存微信服务器每次发来的请求
     *
     * @var array
     */
    public $request;
    
    /**
     * 加密、解密、验证URl
     *
     * @var MsgCrypt
     */
    private $msgCryptor;

    /**
     * 消息是否加密
     *
     * @var boolean
     */
    private $encrypted = false;

    /**
     * 微信请求时的GET参数，加密消息时要用到
     *
     * @var string
     */
    private $timestamp, $nonce, $msg_signature;

    /**
     * 可接受的消息类型和事件类型数组
     *
     * @var array
     */
    protected $msgTypeArray   = array('text', 'image', 'voice', 'video', 'shortvideo', 'link', 'location', 'event');
    protected $eventTypeArray = array('subscribe','unsubscribe','click','view','location','scan','scancode_push','scancode_waitmsg','pic_sysphoto','pic_photo_or_album','pic_weixin','location_select','enter_agent','batch_job_result');


    /**
     * 初始化，判断此次请求是否为验证请求，并以数组形式保存
     *
     * @param string $token 验证信息
     * @param boolean $debug 调试模式，默认为关闭
     */
    public function __construct($token, $aeskey = '', $appid = '', $debug = FALSE) 
    {

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

        $this->msgCryptor = new MsgCrypt($token, $aeskey, $appid);

        // 验证TOKEN
        if ($this->debug == FALSE) {
            $validResult = TRUE;
            if ($wechatType == 1) {
                $validResult = $this->msgCryptor->mpVerifySig($signature, $this->timestamp, $this->nonce);
            } else if (!empty($echostr)) {
                $validResult = $this->msgCryptor->qyVerifySig($this->msg_signature, $this->timestamp, $this->nonce, $echostr);
            }
            if (!$validResult) exit("ERR: 40001\n\n");
        }

        // 企业号解密echostr
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
    private function savePostData($requestData) 
    {
        
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
        echo $sEncryptMsg;
    }

    /**
      * 以文本消息回复
      * @param string $text 要回复的文本
      * @return void
      */
    public function echoText($text)
    {
        $resultStr = sprintf($this->templateText, 
            $this->request['fromusername'], 
            $this->request['tousername'], 
            time(), 
            trim($text)
        );
        $this->echoMsg($resultStr);
    }

    /**
      * 以图文消息回复
      * @param array $items 要回复的内容
      * @return void
      */
    public function echoNews($items)
    {
        $resultStr = sprintf($this->templateNewsBegin, 
            $this->request['fromusername'], 
            $this->request['tousername'], 
            time(), 
            count($items)
        );
        for ($i=0; $i<count($items); $i++) {
            $resultStr .=  sprintf($this->templateNewsContent, 
                isset($items[$i][0])?trim($items[$i][0]):'', 
                isset($items[$i][1])?trim($items[$i][1]):'', 
                isset($items[$i][2])?trim($items[$i][1]):'', 
                isset($items[$i][3])?trim($items[$i][1]):''
            );
        }
        $resultStr .=  $this->templateNewsEnd;
        $this->echoMsg($resultStr);
    }

    /**
      * 进行消息的识别及回复
      * @return void
      */
    public function run()
    {
        $msgType = strtolower($this->request['msgtype']);
        if (in_array($msgType, $this->msgTypeArray)) {
            eval('$this->respon_' . $msgType . '();');
        } else {
            $this->respon_unknown();
        }
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
    public function respon_shortvideo() {}

    // 链接消息
    public function respon_link(){}

    // 位置消息
    public function respon_location() {}

    // 未知消息
    public function respon_unknown()
    {
        if (!$this->debug) {
            return;
        }

        $template = "PHP 报错啦！\r\n\r\n出现未知的消息类型%s";

        $content = sprintf($template, $this->request['event']);

        $this->echoText($content);
        exit;
    }

    // 事件消息
    public function respon_event()
    {
        $event = strtolower($this->request['event']);
        if (in_array($event, $this->eventTypeArray)) {
            eval('$this->respon_event_' . $event . '();');
        } else {
            $this->respon_event_unknown();
        }
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

    // 未知事件
    public function respon_event_unknown()
    {
        if (!$this->debug) {
            return;
        }

        $template = "PHP 报错啦！\r\n\r\n出现未知的事件类型%s";

        $content = sprintf($template, $this->request['event']);

        $this->echoText($content);
        exit;
    }

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
        exit;
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

        $content = sprintf($template, $function_name, implode(',', $args));

        $this->echoText($content);
        exit;
    }
}

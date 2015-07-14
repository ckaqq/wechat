<?php
/**
 * 微信公众号、订阅号模板
 *
 * @author ChenKang <ck@kchen.cn>
 */

require_once __DIR__ . '/MsgCrypt.php';

class Wechat
{
    /**
     * 初始化，判断此次请求是否为验证请求，并以数组形式保存
     *
     * @param string $token 验证信息
     * @param boolean $debug 调试模式，默认为关闭
     */
    public function __construct($wechatType, $token, $aeskey, $appid, $debug = FALSE) {
        //$_GET = json_decode('{"msg_signature":"e8e4f0b5384e47a1fa68bba3f75f09d11f5e8061","timestamp":"1436333042","nonce":"838760432","echostr":"ojtEf3CdFqktmu1XK9ZyazqSIMDJHFqidk6QjRcPpj2euONAU\/0F2xLpKsU\/e8uL1Kw7VCHzzkNtn\/sdRxkpzw=="}', true);

        $this->debug = $debug;

        // 获取请求的参数
        $this->timestamp     = $_GET['timestamp'];
        $this->nonce         = $_GET['nonce'];
        if (!$this->debug && (empty($this->timestamp) || empty($this->nonce)))
            exit("请通过微信访问");
        $this->encrypted     = isset($_GET['encrypt_type']) ? $_GET['encrypt_type'] == 'aes' : FALSE;
        $this->msg_signature = isset($_GET['msg_signature']) ? $_GET['msg_signature'] : '';
        $signature   = isset($_GET['signature']) ? $_GET['signature'] : '';
        $echostr     = isset($_GET['echostr']) ? $_GET['echostr'] : '';
        $requestData = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");

        // 加解密及验证对象
        $this->msgCryptor = new MsgCrypt($token, $aeskey, $appid);

        // 公众号
        if ($wechatType == 1) {

            if (!$debug) {
                $validResult = $this->msgCryptor->mpVerifySig($signature, $this->timestamp, $this->nonce);
                if (!$validResult) {
                    exit("ERR: 40001\n\n");
                }
            }

        // 企业号
        } else {

            $this->encrypted = TRUE;

            if (!empty($echostr)) {

                if ($debug) {
                    $echostr = $this->msgCryptor->qyEchoStr($echostr);
                } else {
                    // 需要返回的明文
                    $sEchoStr = "";
                    $errCode = $this->msgCryptor->VerifyURL($this->msg_signature, $this->timestamp, $this->nonce, $sVerifyEchoStr, $sEchoStr);
                    if ($errCode == 0) {
                        // 验证URL成功
                        $echostr = $sEchoStr;
                    } else {
                        exit("ERR: " . $errCode . "\n\n");
                    }
                }

            }

        }

        // 如果是验证URL则直接输出
        if (!empty($echostr)) {
            exit($echostr);
        }

        if (empty($requestData)) {
            exit('缺少数据');
        }

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
        $xml = $requestData;

        if ($this->encrypted) {
            $errCode = $this->msgCryptor->decryptMsg($this->msg_signature, $this->timestamp, $this->nonce, $requestData, $xml);

            if ($errCode != 0)
                exit($errCode);

        }

        libxml_disable_entity_loader(true);
        $postObj = (array) simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (empty($postObj))
            exit("xml解析失败");
        // 将数组键名转换为小写，提高健壮性，减少因大小写不同而出现的问题
        $this->request = array_change_key_case($postObj, CASE_LOWER);
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
        if ( ! $this->debug) {
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

        $this->responseText(sprintf($template,
            $error_type[$level],
            $msg,
            $file,
            $line
        ));
    }
    /**
     * 处理函数调用异常
     * @param  function $function_name   函数名
     * @param  string $args  参数列表
     * @return void
     */
    public function __call($function_name, $args) {
        if ( ! $this->debug) {
            return;
        }
        $template = "PHP 报错啦！\r\n\r\n函数%s不存在\r\n参数列表为：%s";

        $this->responseText(sprintf($template,
            $function_name,
            implode($args)
        ));
    }
}
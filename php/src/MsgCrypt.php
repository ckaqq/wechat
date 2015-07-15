<?
/**
 * 签名验证及消息加解密
 *
 * @author ChenKang <ck@kchen.cn>
 */

require_once __DIR__ . "/../libraries/official/WXBizMsgCrypt.php";


class MsgCrypt extends WXBizMsgCrypt
{
    public $token;
    public $encodingAesKey;
    public $appId;

    /**
     * 构造函数
     * @param $token string 公众平台上，开发者设置的token
     * @param $encodingAesKey string 公众平台上，开发者设置的EncodingAESKey
     * @param $Corpid string 公众平台的Corpid
     */
    public function __construct($token, $encodingAesKey, $Corpid)
    {
        parent::__construct($token, $encodingAesKey, $Corpid);
        $this->token = $token;
        $this->encodingAesKey = $encodingAesKey;
        $this->appId = $Corpid;
    }

    /**
      *企业号验证签名
      * @param sMsgSignature: 签名串，对应URL参数的msg_signature
      * @param sTimeStamp: 时间戳，对应URL参数的timestamp
      * @param sNonce: 随机串，对应URL参数的nonce
      * @param sEchoStr: 随机串，对应URL参数的echostr
      * @return 成功返回TURE，失败返回FALSE
    */
    public function qyVerifySig($sMsgSignature, $sTimeStamp, $sNonce, $sEchoStr)
    {
        $sha1 = new SHA1;
        $array = $sha1->getSHA1($this->token, $sTimeStamp, $sNonce, $sEchoStr);   
        return $array[0] == 0 ? $array[1] == $sMsgSignature : FALSE;
    }

    /**
      *企业号解密echostr
      * @param sEchoStr: 随机串，对应URL参数的echostr
      * @return 成功返回解密之后的echostr，失败返回对应的错误码
    */
    public function qyEchoStr($sEchoStr) {
        if (strlen($this->encodingAesKey) != 43)
            return "ERR: 40004\n\n";
        $pc = new Prpcrypt($this->encodingAesKey);
        //var_dump(array($sEchoStr, $this->appId));exit;
        $result = $pc->decrypt($sEchoStr, $this->appId);
        return $result[0] == 0 ? $result[1] : $result[0];
    }

    /**
     * 公众号验证签名
     *
     * @param  string $signature 签名
     * @param  string $timestamp 时间戳
     * @param  string $nonce 随机数
     * @return boolean
     */
    public function mpVerifySig($signature, $timestamp, $nonce) {
        $signatureArray = array($this->token, $timestamp, $nonce);
        sort($signatureArray, SORT_STRING);
        return sha1(implode($signatureArray)) == $signature;
    }
}

